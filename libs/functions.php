
<?php
function isStrongPassword($password)
{
    $lower =  false;
    $upper =  false;
    $number =  false;
    $flag = false;
    for ($i=0; $i<strlen($password); $i++)
    {
        if(ctype_upper($password[$i]))
        $upper = true;

        if(ctype_lower($password[$i]))
        $lower = true;
    
        if(ctype_digit((string)$password[$i]))
        $number = true;
    }
    $length = strlen($password) >= 8;  
    if( $length == true && $lower == true && $upper == true && $number == true )
    $flag =  true;
    else 
    $flag = false;
    return $flag;
}

function UserIDGen() 
{
    $UserFlag = 1;
    $datePart = date("dmY"); // Get the date in DDMMYYYY format
    $uniquePart = str_pad(mt_rand(0, 999), 3, '0', STR_PAD_LEFT); // Generate a 3-digit unique number

    return $datePart . $uniquePart . $UserFlag;
}
function AttendanceIDGen()
{
    $I = "ATDN"; //4
    $dateTime = date("dmYHis");  // Get the date in DDMMYYYY HHMMSS format (no gaps tho) 14
    $uniquePart = str_pad(mt_rand(0, 9999999), 7, '0', STR_PAD_LEFT);

    return $I . $dateTime . $uniquePart;
}
function TicketIDGen() 
{
    $datePart = date("dmY"); // Get the date in DDMMYYYY format
    $uniquePart = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT); // Generate a 3-digit unique number

    return $datePart . $uniquePart;
}

function MessagesGen()
{
    $M = "MSG"; // 3
    $dateTime = date("dmYHis");  // Get the date in DDMMYYYYHHMMSS format 14
    $uniquePart = str_pad(mt_rand(0, 999), 3, '0', STR_PAD_LEFT);

    return $M . $dateTime . $uniquePart;
}

function PostIDGen()
{
    $P = "PST"; // 3
    $dateTime = date("dmYHis");  // Get the date in DDMMYYYYHHMMSS format 14
    $uniquePart = str_pad(mt_rand(0, 999), 3, '0', STR_PAD_LEFT);

    return $P . $dateTime . $uniquePart;
}
function PageIDGen()
{
    $datePart = date("dmY"); // Get the date in DDMMYYYY format
    $uniquePart = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT); 

    return $datePart . $uniquePart ;
}

function ReportIDGen()
{
    $datePart = date("dmY"); // Get the date in DDMMYYYY format
    $uniquePart = str_pad(mt_rand(0, 99), 2, '0', STR_PAD_LEFT);
    $uniquePart2 = str_pad(mt_rand(0, 20), 2, '0', STR_PAD_LEFT);

    return $datePart . $uniquePart . $uniquePart2 ;
}
?>
