<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Handles all media the website will ever have to need. It minifies all
 * javascript and stylesheets. Then it compresses every file to gzip and outputs
 * it to the browser, if he accepts it.
 *
 * @package    Media
 * @category   Controllers
 * @author Birkir R Gudjonsson <birkir.gudjonsson@gmail.com>
 * @copyright Copyright (c) 2010, Birkir R Gudjonsson
 */
class Controller_Media extends Controller
{

    public $media = null;

    private $compress = true;

    public function before()
    {
        $this->media = Media::instance('default');
    }

    public function action_index()
    {
        header('HTTP/1.1 404 Not Found');
        exit;
    }

    public function action_css()
    {
        $this->media->file('css/' . $file)
                    ->minify();
    }

    public function action_img()
    {
        $this->media->file('img/' . $file)
                    ->smushit();
    }

    public function action_js()
    {
        $this->media->file('js/' . $file)
                    ->minify();
    }

    public function action_src($file = null)
    {
        $this->media->file('src/' . $file)
                    ->minify()
                    ->smushit();
    }

    public function action_swf()
    {
        $this->media->file('swf/' . $file);
    }

    public function after()
    {
        if ($this->compress) {
            $this->media->gzip();
        }

        $this->media->execute();

        $this->request->headers += $this->media->headers;
        $this->request->response = $this->media->response;
    }

} // End Controller Media
