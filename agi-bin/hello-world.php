#!/usr/bin/php
<?php

#bilo je 10.1.0.204 u pjsip.conf
$stdin = fopen('php://stdin', 'r');
$out = fopen('php://stdout', 'w');
$sline = file("/var/lib/asterisk/agi-bin/ekstenzije.txt", FILE_IGNORE_NEW_LINES);
$path  = "var/lib/asterisk/agi-bin/ekstenzije.txt";
# function for get caller extension 
function getCallerExtesion(){
global $stdin;
set_time_limit(30);
while (!feof($stdin)) {
    $input = trim(fgets($stdin));
    if ($input === '') {
        break;
    }
    list($key, $value) = explode(': ', $input);
    $agi_env[$key] = trim($value);
}
$extension = isset($agi_env['agi_extension']) ? $agi_env['agi_extension'] : '';
return $extension;
}

# playAudio function 
function playAudio($file)
{
    global $out, $stdin;
    fwrite($out, "STREAM FILE $file \"\"\n");
    $response = "";
    while (!feof($stdin)) {
        $response .= fgets($stdin);
        if (strpos($response, "result") !== false) {
            break; // Break loop when result received
        }
    }
}

# function for input 
function getInput($maxDigits)
{
    global $stdin, $out;
    fwrite($out, "GET DATA hello-world 5000 $maxDigits\n");
    $resp = trim(fgets($stdin));
    $res = substr($resp, strlen($resp) - $maxDigits, $maxDigits);
    return $res;
}

# function for validating extension 
function isValidExtension($extension)
{
    global $sline;
    foreach ($sline as $line) {
        $parts = explode(":", $line);
        if (count($parts) === 2) {
            $extensionFromFile = $parts[0];
            $str = intval($extensionFromFile);
            if ($str === $extension) {
                return true;
            }
        }
    }
    return false;
}
function isValidPassword($extension, $password)
{
    global $sline;
    foreach ($sline as $line) {
        $parts = explode(":", $line);
        if (count($parts) === 2) {
            $extensionFromFile = $parts[0];
            $str = intval($extensionFromFile);
            $passwordFromFile = $parts[1];
            if ($str === $extension && $passwordFromFile === $password) {
                return true;
            }
        }
    }
    return false;
}

# function for delete extension
function deleteLineWithExtension($filename, $extension)
{
    $lines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return false;
    }
    $found = false;
    foreach ($lines as $key => $line) {
        list($ext, $password) = explode(':', $line, 2);
        if ($ext == $extension) {
            unset($lines[$key]);
            $found = true;
            break;
        }
    }
    if (!$found) {
        return false;
    }
    $lines = array_values($lines);
    $result = file_put_contents($filename, implode("\n", $lines) . "\n");
    if ($result === false) {
        return false;
    }
    return true;
}

# function for validating password
function validatePassword($password)
{
    if (strlen($password) !== 4) {
        return false;
    }
    if (!ctype_digit($password)) {
        return false;
    }
    return true;
}

# functions for Dial
function sendAGICommand($command)
{
    global $stdin, $out;
    fwrite($out, "$command\n");
    fflush($out);
    return trim(fgets($stdin));
}

function Dialer($extension)
{
     sendAGICommand ("Exec Dial PJSIP/$extension");
}

function switchPassword($extension, $newPassword)
{
    global $path;
    if (isValidExtension($extension)){
            deleteLineWithExtension($path, $extension);
    }
    $newFileContent = "$extension:$newPassword";
    $result = file_put_contents($path, $newFileContent, FILE_APPEND);
    if ($result){
        return true;
    }
    return false;
}
#main function for IVR
function mainIVR()
{
    $caller2 = intval(getCallerExtesion());
    if (isValidExtension($caller2)) {
        $password = getInput(4);
        if (isValidPassword($caller2, $password)) {
            playAudio("vm-mismatch");
        }
        else {
            playAudio("invalid");
            return;
        }
    }
    playAudio("hello");
    while (true) {
        $option = intval(getInput(1));
        switch ($option) {
            case 1:
                playAudio("beep");
                $extension2 = intval(getInput(3));
                if ($extension2 === $caller2){ 
                    playAudio("goodbye");
                }
                else {
                    if (isValidExtension($extension2)) {
                        Dialer(strval($extension2));
                    }
                    else {
                        playAudio("one-moment-please");
                    }
                }
                break;
            case 2:
                playAudio("iPhone");
                if (isValidExtension($caller2)) {
                    $password = getInput(4);
                    if (isValidPassword($caller2, $password)) {
                        $newpass = getInput(4);
                        playAudio("activated");
                        $newPassword = getInput(4);
                        if ($newPassword === $newpass) {
                            if (switchPassword($caller2, $newPassword)){
                                playAudio("vm-passchanged");
                            }
                            else{
                                playAudio("vm-invalid-password");
                            }
                        }
                    } 
                    else {
                        playAudio("invalid");
                    }
                } 
                else {
                    $password = getInput(4);
                    if (validatePassword($password)){
                        switchPassword(238, $password);
                    }
                    else {
                        playAudio("vm-invalid-password");
                    }
                }
                break;
            case 3:
                playAudio("number");
                continue 2;

            default:
                playAudio("second");
                break;
        }
    }
}

#start IVR
mainIVR();

#close the streams
fclose($stdin);
fclose($out);
fclose($sline2)

?>