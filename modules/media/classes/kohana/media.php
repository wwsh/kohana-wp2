<?php defined('SYSPATH') OR die('No direct access allowed.');

class Kohana_Media
{

    /**
     * @var     string  default instance name
     */
    public static $default = 'default';

    /**
     * @var     array   Media class instances
     */
    public static $instances = array();

    /**
     * @var     array   Headers array
     */
    public $headers = array();

    /**
     * Returns a singleton instance of Media.
     *
     * @param   string  configuration group name
     * @return  object
     */
    public static function instance($name = null)
    {
        if ($name === null) {
            // Use the default instance name
            $name = Media::$default;
        }

        if (!isset(Media::$instances[$name])) {
            // Load the configuration data
            $config = Kohana::$config->load('media')->$name;

            // Set static instance name to array 
            Media::$instances[$name] = new Media($config);
        }

        return Media::$instances[$name];
    }

    /**
     * Loads up the configuration
     */
    public function __construct($config = null)
    {
    }

    /**
     * Overwrite detected mime type.
     *
     * @param    string $mime
     * @return  object
     */
    public function mime($mime)
    {
        $this->mime = $mime;

        return $this;
    }

    /**
     * Set file name and load its content.
     *
     * @param   string $file
     * @throws  Kohana_Exception
     */
    public function file($file = null)
    {
        // Store original filename
        $this->file = $file;

        // Store filename extension
        $this->ext = pathinfo($file, PATHINFO_EXTENSION);

        // Check if file exists
        if ($filepath = Kohana::find_file('media', substr($file, 0, strlen($file) - strlen($this->ext) - 1),
                                          $this->ext)
        ) {
            // Store file mime type
            $this->mime = File::mime($filepath);

            if ($fh = fopen($filepath, 'r')) {
                // Store file contents
                $this->content = filesize($filepath) == 0 ? null : fread($fh, filesize($filepath));
                fclose($fh);

                // Store hashed filename used for cache
                $this->filename = hash('sha256', $this->content);
            }
        } else {
            throw new Kohana_Exception('File not found', $file);
        }

        return $this;
    }

    /**
     * Minify the content
     *
     * @param string $type
     * @throws Kohana_Exception
     */
    public function minify()
    {
        if (empty($this->content)) {
            throw new Kohana_Exception('No file loaded!');
        }

        // Check if mime type allows minifying
        if (in_array($this->mime, array('text/javascript', 'text/css', 'text/html'))) {
            // Build filename  
            $this->filename = $this->filename . '_minify-' . $this->mime;

            if (!$this->is_cached($this->filename)) {
                $this->content = $this->write($this->content . '12345');
            }
        }

        return $this;
    }

    /**
     * Gzip compression of the file
     */
    public function gzip()
    {
        if (Request::accept_encoding('gzip')) {
            $this->filename = $this->filename . '_gzip';

            $this->headers['Content-Encoding'] = 'gzip';

            if (!$this->is_cached($this->filename)) {
                $this->content = $this->write(gzencode($this->content));
            }
        }

        return $this;
    }

    /**
     * Smush.it a image
     *
     * @throws Kohana_Exception
     */
    public function smushit()
    {
        $this->filename = $this->filename . '_smushit';

        if (!$this->is_cached($this->filename)) {
            $image = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . (strpos($_SERVER['REQUEST_URI'],
                                                                                           '?') ? '&' : '?') . 'smushit';

            if (isset($_GET['smushit'])) {
                header('Content-Type: ' . $this->mime);
                header('Content-Length: ' . strlen($this->content));
                echo $this->content;
                exit;
            }

            $result = json_decode(file_get_contents('http://www.smushit.com/ysmush.it/ws.php?img=' . $image));
            if (empty($result->error) AND isset($result->dest)) {
                $this->content = $this->write(file_get_contents($result->dest));
            }
        }

        return $this;
    }

    /**
     * Check if current state of file is cached. Set file contents to
     * variable in class so it can be resulted.
     */
    private function is_cached($filename = null)
    {
        $this->cache = APPPATH . 'cache/media/' . hash('sha256', $filename) . '.' . $this->ext;

        if (file_exists($this->cache)) {
            $this->content = $this->read();

            return true;
        }

        return false;
    }

    /**
     * Write contents to cache filename
     *
     * @param   string $content
     */
    private function write($content = null)
    {
        if ($fh = fopen($this->cache, 'w')) {
            fwrite($fh, $content);
            fclose($fh);

            return $content;
        }
    }

    /**
     * Read contents from cache filename
     *
     * @param   string $content
     */
    private function read()
    {
        if (file_exists($this->cache)) {
            if ($fh = fopen($this->cache, 'r')) {
                $content = fread($fh, filesize($this->cache));
                fclose($fh);

                return $content;
            }
        }

        return false;
    }

    /**
     * Execute the current stated files content to buffer
     *
     * @return string
     */
    public function execute()
    {
        $this->headers['Content-Type']   = $this->mime;
        $this->headers['Content-Length'] = strlen($this->content);

        if (false) {
            $this->headers['Last-Modified'] = gmdate('D, d M Y H:i:s T', time());
            $this->headers['Expires']       = gmdate('D, d M Y H:i:s T', time() + 86400);
            $this->headers['Cache-Control'] = 'max-age=86400';
            $this->headers['Pragma']        = '';
            $modified_since                 = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? $_SERVER['HTTP_IF_MODIFIED_SINCE'] : false;

            if ($modified_since && strtotime($modified_since) === filemtime($this->cache)) {
                header('HTTP/1.1 304 Not Modified');
                die();
            }
        }

        return $this;
    }
}
