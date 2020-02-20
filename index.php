<?php

date_default_timezone_set('Europe/Budapest');  //setup php timezone

class CalculateDueDate {
//debug test variables (feel free to use :) )
   // var $debug = TRUE; //print out debuging text
   // var $fixTime = "2020-02-21 9:00:00"; //fixing a sending time
    
// customizable variables
    var $workdays = [1,2,3,4,5]; //workdays when accepting request, using a php date function N format value: 1 monday - 7 saturday
    var $timeStart = 9; //starting time in hour ( 0-23 value, 0 is midnight )
    var $timeEnd = 17; //ending time in hour ( 0-23 value, 0 is midnight )
    var $workingHoursNeedNet = 16; //net working time in hour
    
    
// not customizable variables (need for system working)
    var $currentTime = 0; //storing current time
    var $isReversedWorkDay = FALSE;
    var $currentlyWorkingHourADay = 0;
    var $maximumWorkingHourADay = 0;
    var $workingDayIsNeed = 0;
    var $workingHoursNeedGross = 0; //gross working time in hour
    var $allDayIsNeed = 0;
    
    function __construct() {
        $this->checkSendIsFair(); //Checking day and hour, how is a good time for a report, and calculate available working time
        $this->countDayIsNeed(); //count the needing woorkdays number
        $this->countGrossHour(); //count the full gross working time what is need
        
        $this->printDone(); //print out the issue resolve time
    }
    
    /* Checking day and hour, how is a good time for a report, and calculate available working time
     * 
     */
    function checkSendIsFair() {
        $this->currentTime = time(); //storing running time, we want to avoid a possible "time jump" factor occurrence.. (this have a little chance, but not impossible)
        if ( isset($this->fixTime) ) { //fixing time if we want
            $this->currentTime = strtotime($this->fixTime);
        }
        
        $dateDay = date('N',$this->currentTime); //actual day
        $dateHour = date('G',$this->currentTime); //actual hour

        $dayIsFair = ( in_array($dateDay,$this->workdays) ? TRUE : FALSE ); //this is a good day for report?

        $this->isReversedWorkDay = ( $this->timeStart > $this->timeEnd ? TRUE : FALSE ); //checking if the workday is reaching over midnight, if the answer is true, we use a different script to count a work time
        if ( $this->isReversedWorkDay ) { //if reach past midnight
            $hourIsFair = ( ($this->timeStart <= $dateHour || $dateHour < $this->timeEnd) ? TRUE : FALSE ); //this is a good day for report?
            $this->currentlyWorkingHourADay = $this->timeEnd - $dateHour + 24 - 1; //still allow worktime in a day
            $this->maximumWorkingHourADay = $this->timeEnd - $this->timeStart + 24; //maximum allow worktime in a day
        } else { //normális munkanap
            $hourIsFair = ( ($this->timeStart <= $dateHour && $dateHour < $this->timeEnd) ? TRUE : FALSE ); //this is a good day for report?
            $this->currentlyWorkingHourADay = $this->timeEnd - $dateHour - 1; //still allow worktime in a day
            $this->maximumWorkingHourADay = $this->timeEnd - $this->timeStart; //maximum allow worktime in a day
        }

        if ( !$dayIsFair || !$hourIsFair ) { $this->notInWorkingTime(); } //testing previous day and hour, how is a fair or not, if not we going to exit part of the script
    }
    
    /* Counting how many "clean" workday is need for repair a issue
     * 
     */
    function countDayIsNeed() {        
        if ( $this->workingHoursNeedNet > $this->currentlyWorkingHourADay ) { //check if a current day have enought time to make a issue to complette, or need more day
            $this->workingDayIsNeed += 1;
            $i = $this->workingHoursNeedNet - $this->currentlyWorkingHourADay;
            if ( $i > $this->maximumWorkingHourADay ) {
                $this->workingDayIsNeed += ceil($i/$this->maximumWorkingHourADay);
            }
        }
    }
    
    /* Calculate gross working hour
     * 
     */
    function countGrossHour() {        
        if ( $this->workingDayIsNeed === 0 ) { $this->workingHoursNeedGross = $this->workingHoursNeedNet; return TRUE; }
        
        $hourNeedToNextDay = 24 - $this->maximumWorkingHourADay; //variable for step a day
        
        $counterWorkingNet = $this->workingHoursNeedNet;
        
        for ( $i=0,$c=0;$i<=$this->workingDayIsNeed;){
            $n = date('N',strtotime("+$c day",$this->currentTime)); //current day
            $isWorkday = ( in_array($n,$this->workdays) ? TRUE : FALSE );
            $this->allDayIsNeed += 1;
            if ( $i === 0 ) {
                $counterWorkingNet -= $this->currentlyWorkingHourADay;
                $this->workingHoursNeedGross += $this->currentlyWorkingHourADay;
                $i++;
            } elseif ( $isWorkday ) { //current day is a workday or not?
                $this->workingHoursNeedGross += $hourNeedToNextDay;
                if ( $this->maximumWorkingHourADay < $counterWorkingNet ) {
                    $counterWorkingNet -= $this->maximumWorkingHourADay;
                    $this->workingHoursNeedGross += $this->maximumWorkingHourADay;
                } else {
                    $this->workingHoursNeedGross += $counterWorkingNet;
                    $counterWorkingNet = 0;
                    break;
                }
                $i++;
            } else { //not work day, add a full 24 hour to a counter
                $this->workingHoursNeedGross += 24;
            }
            $c++;
        }
    }
    
    /* Print the issure resolve time
     * 
     */
    function printDone () {
        $resolvedDate = date("Y-m-d H:i:s",strtotime("+$this->workingHoursNeedGross hour", $this->currentTime));
        print $resolvedDate;
        $this->printDebug(); //print debug text if allowed, $this->debug=TRUE is need
        exit;
    }

    /*
     * This is not a worktime, exiting with a message.
     */
    function notInWorkingTime () {
        print "Sorry, but we are out of working time!";exit;
    }
    
    /* Print out debuging text,\nwee need $this->debug=TRUE for a work.
     *
     */
    function printDebug() {
        if ( !isset($this->debug) || $this->debug !== TRUE ) { return FALSE; }
        print "\n\ndebuging: \n";
        print "is reversed: ";print $this->isReversedWorkDay;print " \n";
        print "workhour is need (net): ".$this->workingHoursNeedNet." \n";
        print "workday is need (gross): ".$this->workingHoursNeedGross." \n";
        print "workhour a day (current): ".$this->currentlyWorkingHourADay." \n";
        print "workhour a day (full day): ".$this->maximumWorkingHourADay." \n";
        print "day is need: ".$this->allDayIsNeed." \n";
        print "work start time: ".$this->timeStart." \n";
        print "work end time: ".$this->timeEnd." \n";
    }
}

new CalculateDueDate();

?>