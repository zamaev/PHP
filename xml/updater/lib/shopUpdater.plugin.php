<?php

class shopUpdaterPlugin extends waPlugin
{
    public function backendProducts()
    {
        return array(
            'sidebar_top_li'  => '<li><a href="/webasyst/shop/?plugin=updater" target="_blank"><i class="icon16 update"></i>Обновить товары</a></li>',
        );
    }

}
