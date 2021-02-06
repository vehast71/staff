<?
require_once "functions.php";
if(isset($_POST['href'])){
    report_for_day($_POST['href']);
}