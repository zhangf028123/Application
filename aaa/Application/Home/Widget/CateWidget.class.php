<?php

namespace Home\Widget;

use Think\Controller;

class CateWidget extends Controller
{
    public function menu(){
        return 'menuWidget';
    }
    public function menu1(){
        echo 'menuWidget';
    }
}
