<?php

namespace Sabo\Interface;

interface MaintenanceManagerInterface extends ShowableInterface
{
	// return if client is allowed to continue in website during maintenance
	public function can_continue_in_website():bool;
}