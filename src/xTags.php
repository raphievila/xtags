<?php

namespace xTags;

//Author: Rafael Vila
/*
 *Version: 3.0.2
 *Last Modified: May 30th, 2016 02:36
 *License:
    xTags let you code html tags easily and faster through PHP without
    getting into too much confusion in your code.
    Copyright (C) 2016  Rafael Vila - Revolution Visual Arts

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

class xTags
{
    public $str;
    const s1 = ',';
    const s2 = ':';
    const s3 = '-';
    const s4 = '&';
    private $separators = array(self::s1, self::s2, self::s3, self::s4);
    private $substitute = array('~', '..', '|', '&&');
    public $breaks;
    public $switch;

    public function __construct()
    {
        $this->breaks = $this->separators;
        $this->switch = $this->substitute;
    }

    //dynamic call to generate tags
    public function __call($tag, $args)
    {
        $att = isset($args[1]) ? $args[1] : '';
        $close = isset($args[2]) ? $args[2] : '';

        return $this->tag($tag, $args[0], $att, $close);
    }

    public function tag($tagName, $content, $attList = '', $sclose = 0)
    {
        $this->str = $this->gTag($tagName, $content, $attList, $sclose);

        return $this->str;
    }

    public function processText($str)
    {
        $pr = str_replace($this->separators, $this->substitute, $str);

        return $pr;
    }

    public function unprocessText($str)
    {
        $pr = str_replace($this->substitute, $this->separators, $str);

        return $pr;
    }

    private function processString($string, $asArray = false)
    {
        $aList = explode(self::s1, $string);
        $jn = null;
        if (is_array($aList)) {
            foreach ($aList as $al) {
                $at = explode(self::s2, $al);
                $a = $at[0];
                $e = (isset($at[1])) ? str_replace('--', '|', str_replace($this->switch, $this->breaks, $at[1])) : '';
                if (!$asArray && !empty($a)) {
                    $jn[] = $a.'="'.$e.'"';
                } elseif (!empty($a)) {
                    $jn[$a] = $this->unprocessText($e);
                }
            }
        }

        return $jn;
    }

    private function jsonAttr($attList, $att)
    {
        foreach ($attList as $k => $v) {
            $adding[] = '"'.$k.'":"'.$v.'"';
        }
        $attributes = $att.','.join(',', $adding);

        return $attributes;
    }

    private function objAttr($attList, $att)
    {
        if (is_object($att)) {
            foreach ($attList as $k => $v) {
                $att->{$k} = $v;
            }
        } else {
            foreach ($attList as $k => $v) {
                $att[$k] = $att;
            }
        }

        return $att;
    }

    private function stringAttr($attList, $att)
    {
        if (count($attList) > 0) {
            $arr = array();
            foreach ($attList as $k => $v) {
                $arr[] = $k.':'.$v;
            }

            return $att.','.join(',', $arr);
        } else {
            return $att;
        }
    }

    private function addAttributes($attList, $att)
    {
        $attributes = '';
        if (is_string($att) && !$this->checkStringIsJson($att)) {
            $att = $this->processStringAsAray($att);
        }
        if ($this->checkStringIsJson($att)) {
            $attributes = $this->jsonAttr($attList, $att);
        } elseif (is_object($att) || is_array($att)) {
            $attributes = $this->objAttr($attList, $att);
        } else {
            $attributes = $this->stringAttr($attList, $att);
        }

        return $attributes;
    }

    private function checkStringIsJson($json)
    {
        if (is_array($json)) {
            return false;
        }

        return is_object(json_decode($json));
    }

    private function processJson($string)
    {
        $obj = json_decode($string);
        foreach ($obj as $k => $v) {
            $jn[] = $k.'="'.$v.'"';
        }

        return (count($jn) > 0) ? $jn : false;
    }

    private function gTag($nm, $ct, $att, $sclt)
    {
        $jn = '';
        $ls = '';
        if (is_string($att)) {
            if ($this->checkStringIsJson($att)) {
                $jn = $this->processJson($att);
            } else {
                $jn = $this->processString($att);
            }
        } elseif (is_array($att) || is_object($att)) {
            $jn = array();
            $this->comment(gettype($att));
            foreach ($att as $k => $v) {
                $jn[] = $k.'="'.$v.'"';
            }
        }
        if (is_array($jn)) {
            $list = join(' ', $jn);
            $ls = ' '.$list;
        }
        if ($sclt == 1) {
            switch ($nm) {
                case 'img': case 'link': $att = 'src'; break;
                case 'area': $att = 'href'; break;
                default: $att = 'value';
            }
        } else {
            $att = 'value';
        }
        $r = ($sclt == 0) ? '<'.$nm.$ls.'>'.$ct.'</'.$nm.'>' : '<'.$nm.$ls.' '.$att.'="'.$ct.'" />';

        return $r;
    }

    //html comments
    public function comment($string)
    {
        return '<!-- '.htmlspecialchars($string).' -->';
    }

    //complex tags predefined to simplify coding
    public function frm($txt, $att = '', $a = '', $m = '', $e = '')
    {
        $attList = array();
        if (!empty($a)) {
            $attList['action'] = $a;
        }
        if (!empty($m)) {
            $attList['method'] = $m;
        }
        if (!empty($e)) {
            $attList['enctype'] = $e;
        }
        $newatt = $this->addAttributes($attList, $att);

        return $this->tag('form', $txt, $newatt);
    }

    public function group($txt, $legend = 'Legend', $attr = '')
    {
        return $this->tag('fieldset', $this->legend($legend).$txt, $attr);
    }

    public function tbl($txt, $att = '', $b = 0, $p = 0, $s = 0)
    {
        $stdAtt = array('border' => $b, 'cellspacing' => $s, 'cellpadding' => $p);
        $newatt = $this->addAttributes($stdAtt, $att);

        return $this->tag('table', $txt, $newatt);
    }

    //Self closing predefined tags
    public function img($txt, $attr = '')
    {
        return $this->tag('img', $txt, $attr, 1);
    }

    public function input($txt, $attr = '')
    {
        return $this->tag('input', $txt, $attr, 1);
    }

    public function link($txt, $attr = '')
    {
        return $this->tag('link', $txt, $attr, 1);
    }

    public function meta($txt, $attr = '')
    {
        return $this->tag('meta', $txt, $attr, 1);
    }

    //script tag
    public function script($txt, $type = '', $method = 'text')
    {
        $attr = "type:$method/";
        if (empty($type)) {
            $type = 'js';
        }
        switch ($type) {
            case 'js': $attr .= 'javascript'; break;
            case 'py': $attr .= 'python'; break;
            case 'vb': $attr .= 'vbscript'; break;
            case 'coffee': $attr .= 'coffeescript'; break;
            default: $attr .= $type;
        }

        return $this->tag('script', $txt, $attr);
    }

    //picture tag
    public function picture_tag($params = false)
    {
        $requiredParams = ['dir', 'fileName', 'alt'];
        $availableParams = ['dir', 'fileName', 'alt', 'extension', 'class', 'responsivePattern'];

        if (!$params) {
            throw new Exception('You need to provide parameters', 500);
        } elseif (!is_object($params) && !is_array($params)) {
            throw new Exception('Parameters has to be an object or array.', 500);
        } else {
            foreach ($requiredParams as $required) {
                if (!isset($params->{$required})) {
                    $available = ' Available parameters: '.join(', ', $availableParams).'.';

                    throw new Exception('Parameter '.$required.' is required.'.$available, 500);
                }
            }
        }

        if (is_array($params)) {
            $params = (object) $params;
        }

        $dir = $params->dir;
        $fileName = $params->fileName;
        $alt = $params->alt;
        $class = (!isset($params->class)) ? 'responsive-image' : $params->class;
        $extension = (!isset($params->extension)) ? 'jpg' : $params->extension;
        $dash = (!isset($params->dash)) ? '-' : $params->dash;
        $default = (!isset($params->default)) ? $dash.'3x' : $dash.$params->default;
        $separator = DIRECTORY_SEPARATOR;
        $ROOT = filter_input(INPUT_SERVER, 'DOCUMENT_ROOT', FILTER_SANITIZE_STRING);

        if (!isset($params->responsivePattern) || (isset($params->responsivePattern) && !is_array($params->responsivePattern) && !is_object($params->responsivePattern))) {
            $responsePattern = array(
                '0.75x' => 360,
                '1x' => 720,
                '1.5x' => 1000,
                '2x' => 1440,
                '3x' => 1920,
                '4x' => 3840,
                '8x' => 7680,
            );
        } else {
            $responsePattern = $params->responsivePattern;
        }

        arsort($responsePattern);

        $r = '<picture class="'.$class.'">';

        foreach ($responsePattern as $size => $minWidth) {
            $url = $dir.'/'.$fileName.$dash.$size.'.'.$extension;
            $rooturi = (!preg_match('/^http[s]?/', $dir)) ? $ROOT.$separator.str_replace('/', $separator, trim($url, '/')) : false;

            if (($rooturi && file_exists($rooturi)) || (!$rooturi && !empty($url))) {
                $r .= '<source media="(min-width: '
                    .$minWidth.'px)" srcset="'
                    .$url.'">';
            }
        }

        $r .= '<img src="'.$dir.'/'.$fileName.$default.'.'.$extension.'" class="'.$class.'">';

        $r .= '</picture>';

        return $r;
    }
}
