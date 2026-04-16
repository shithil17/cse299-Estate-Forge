<?php
require_once "ConcreteUser.php";

//Factory method (Creator according to slidfe)
class UserActionFactory {
    public static function createAction($userFlag) {
        switch ($userFlag) {
            case 1:
                return new StudentAction();
            case 2:
                return new FacultyAction();
            case 3:
                return new AdminAction();
            case 4:
                return new AccountantAction();
            default:
                //Exceptional case never exists but anyways :>
                throw new Exception("Invalid user flag.");
        }
    }
}
?>