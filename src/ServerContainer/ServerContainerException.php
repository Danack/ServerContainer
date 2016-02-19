<?php

namespace ServerContainer;

/**
 * Class ServerContainerException
 * Represents a generic error condition in the ServerContainer application.
 * Error message should be shown to administrators / people on the command line.
 * Should not be shown directly to end-users / people on a HTTP interface all of the time.
 * @package ServerContainer
 */
class ServerContainerException extends \Exception { }
