<?php
namespace System\Interfaces;
/**
 * 
 */
interface IUserAuth
{
    function hasRequiredSignUpFields();
    function hasRequiredLoginFields();
}