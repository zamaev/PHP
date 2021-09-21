<?php

class shopUpdaterPluginFrontendUpdateController extends waController
{
    public function execute()
    {
        shopUpdaterStart::start();
    }
}