<?php
/*
 * ...
 *
 * @package   This file is part of the Kit.cms
 * @link      http://kitcms.ru
 * @author    Anton Popov <a.popov@kit.team>
 * @copyright Copyright (c) Kit.team
 * @copyright (c) 2013 Ivan Shalganov
 */

namespace Classes\Template;

use Fenom;

class Accessor {
    public static $vars = array(
        'get'     => '$_GET',
        'post'    => '$_POST',
        'session' => '$_SESSION',
        'cookie'  => '$_COOKIE',
        'request' => '$_REQUEST',
        'files'   => '$_FILES',
        'globals' => '$GLOBALS',
        'server'  => '$_SERVER',
        'env'     => '$_ENV'
    );

    /**
     * @param string $chain template's method name
     * @param Tokenizer $tokens
     * @param Template $tpl
     * @return string
     */
    public static function parserChain($chain, Fenom\Tokenizer $tokens, Template $tpl)
    {
        return $tpl->parseChain($tokens, '$tpl->getStorage()->'. $chain);
    }

    public static function compile(Fenom\Tokenizer $tokens, Template $tpl)
    {
        $tokens->skip('(');
        $name = $tpl->parsePlainArg($tokens, $static);
        if($tokens->is(',')) {
            $tokens->skip()->need('[');
            $vars = $tpl->parseArray($tokens) . ' + $var';
        } else {
            $vars = '$var';
        }
        $tokens->skip(')');
        return '$tpl->getStorage()->compileCode('.$name.', "Accessor compile")->fetch('.$vars.')';
    }
}
