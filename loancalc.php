<?php
define("DEBUGGING", false);
class studentLoanCalc
{
	public $type;
	public $salary;
	public $loantotal;

    private $avrgInterest = 1.5;
    private $types = array(
        "PRE" => 15795,
        "POST" => 21000
    );
    private $typesyears = array(
        "PRE" => 25,
        "POST" => 30
    );
    private $replaymentPercent = 9;

    public function __construct($type, $salary, $loantotal)
    {
        $this->type = $type;
        $this->salary = $salary;
        $this->loantotal = $loantotal;
    }

    public function getFromSalary($options)
    {
        if($this->salary >= $this->types[$this->type]) $chargeable = $this->salary - $this->types[$this->type];
        else $chargeable = 0;
        //print $chargeable;
        $yearlyPayments = $this->getPercent($chargeable, $this->replaymentPercent);
        $currentammount = $this->loantotal;
        $counter = 0;
        $interestPaid = 0;
        $interests = 0;
        $totalpaid= 0;
        $lastyear = 0;
        while( $counter <$this->typesyears[$this->type] AND $currentammount > 0)
        {

            if($counter > 0)
            {
                if(isset($options['salaryIncrease']) && $options['salaryIncrease'] )
                {
                    $this->salary = $this->salary + $this->getPercent($this->salary, $options['salaryIncreaseCount']);
                }
                if($this->salary >= $this->types[$this->type]) $chargeable = $this->salary - $this->types[$this->type];
                else $chargeable = 0;
                $yearlyPayments = $this->getPercent($chargeable, $this->replaymentPercent);
                $interests = $this->getPercent($currentammount, $this->avrgInterest);
                $interestPaid = $interestPaid + $interests;
                $currentammount = $this->addInterest($currentammount);
            }
            $totalpaid = $totalpaid + $yearlyPayments;
            if($currentammount<$yearlyPayments) {$lastyear = $currentammount; $currentammount = 0;}
            $currentammount = $currentammount - $yearlyPayments;
            #### Debuging ####
            if(DEBUGGING)
            {
                echo "Salary: ".$this->salary ."<br /><br />";
                echo "Total Paid: ".$totalpaid ."<br /><br />";
                echo "interests: ".$interests ."<br /><br />";
                echo "Repayments: ".$yearlyPayments ."<br /><br />";
                echo "Current Amount: ".$currentammount ."<br /><br />";
            }
            ###################
            $counter++;
        }
        $avgyear = $totalpaid / $counter;
        $avgmonth = $avgyear /12;
        $returnArr = array(
            "years" => $counter,
            "loanafter" => number_format($this->loantotal + $interestPaid, 2),
            "totalpaid" => number_format($totalpaid, 2),
            "interestpaid" => number_format($interestPaid, 2),
            "lastyear" => number_format($lastyear, 2),
            "yearlypayments" => number_format($avgyear, 2),
            "monthlypayments" => number_format($avgmonth, 2),
            "message" => $this->getComent($counter),
                            );
        return $returnArr;

    }
    private function getComent($years)
    {
        $maxyear = $this->typesyears[$this->type];
        switch (true)
        {
            case ($years == $maxyear) : return "It looks like your debt will get written off before you have time to repay it in full!";break;
            case ($years < $maxyear && $years >= $maxyear * 0.90 ) : return"You would just about repay your loan in full before the \"write off\" period";break;
            case ($years < $maxyear *0.90 && $years >= $maxyear *0.30 ) : return "You are within the normal range when it comes to repaying your student loan ;)";break;
            case ($years < $maxyear *0.30 ) : return"Wow, you'll be repaying your loan at an unusual rate, good on you!";break;
        }
    }
    private function getPercent($val, $percent)
    {
        return ($val / 100 * $percent);
    }
    private function addInterest($val)
    {
        return $val + ($this->getPercent($val, $this->avrgInterest));
    }

}

if(isset($_GET['type']) && isset($_GET['basesalary']) && isset($_GET['loanammount']))
{
    $type = $_GET['type'];
    $salary = $_GET['basesalary'];
    $loan = $_GET['loanammount'];
    $salaryIncrease = false;
    $salaryIncreaseCount = 0;
    if(isset($_GET['salaryincreasebool']) && $_GET['salaryincreasebool'] == "true") $salaryIncrease = true;
    if(isset($_GET['salaryincrease'])) $salaryIncreaseCount = $_GET['salaryincrease'];
    $options = array(
        "salaryIncrease" => $salaryIncrease,
        "salaryIncreaseCount" => $salaryIncreaseCount,
    );

    $calc = new studentLoanCalc( strtoupper($type), $salary, $loan);

    $result = $calc->getFromSalary($options);
    #### Debuging #####
    if(DEBUGGING) print_r($result);
    ###################
    else
    {
        header('Cache-Control: no-cache, must-revalidate'); header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); header('Content-type: application/json');
        echo json_encode($result);
    }
}