<?php

namespace Omnilang\Model;

/** What a text is: plain, or HTML whose tags the engine keeps and translates around. */
enum TextFormat: string
{
    case Plain = 'plain';
    case Html = 'html';
}
