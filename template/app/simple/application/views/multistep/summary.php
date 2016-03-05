<?php
/**
 * This is an example of code-backed Mustache view. Note it is a plain object.
 *
 * Renders summary.mustache
 */
class Views_Multistep_Summary
{

    // Remember, this object gets instance variables as well as local data passed to it. Price is passed in
    // as part of the render_class call.
    function total()
    {
        return '$ ' . number_format($this->price, 2);
    }

    function items()
    {
        return Kwp::objectify($this->order['items'], 'i', 'value', 1);
    }

    function instructions()
    {
        return $this->order['instructions'];
    }

}
