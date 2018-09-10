<?php

namespace DoveTale;

class Required_Param_Missing_Exception extends \RuntimeException {

	protected $message = 'One or more required parameters missing';

}
