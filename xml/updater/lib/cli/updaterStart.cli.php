<?php

class updaterStart extends waCliController
{
	public function execute()
	{
		echo 'start';
        shopUpdaterStart::start();
	}
}