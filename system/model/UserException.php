<?php
//   UserException
// +------------------------------------------------------------------------+
// | PHP version 5                                                          |
// +------------------------------------------------------------------------+
// |                                                                        |
// | Exceptions with messages readable for the user.                        |
// |                                                                        |
// +------------------------------------------------------------------------+

/**
 * This class is made for giving feedback that is 
 * understandable for the user of the site.
 * No error codes or technical language here.
 *
 * This exceptions are supposed to be shown directly
 * to the user.
 */
class UserException extends BofhException {

    //TODO: fill it up

    /**
     * To make it easy, just prints out the error
     */
    public function __construct($err) {

        View::addMessage($err);


    }

    /**
     * For easier output of the error message.
     */
    public function __toString() {

        return $this->getMessage();

    }

}
