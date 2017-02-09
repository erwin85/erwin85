<?php
require_once './pChart/pData.class';
require_once './pChart/pChart.class';

// Logarithmic plot?
if (max($counts) > 100) {
    $counts = array_map('log10', $counts); // Take log10 of all values.
    $log = True;
}

// Ticks spacing
if (count($counts) > 200) {
    $twidth = 50;
} elseif (count($counts) > 100) {
    $twidth = 25;
} elseif (count($counts) > 50) {
    $twidth = 10;
} else {
    $twidth = 5;
}

// Dataset definition
$DataSet = new pData;
$DataSet->AddPoint($counts,  'Serie1');
$DataSet->AddAllSeries();
$DataSet->SetSerieName($user,  'Serie1');
$DataSet->SetYAxisName("Edit count [#]");
$DataSet->SetXAxisName("Project");

// Initialise the graph
$Test = new pChart(640, 430); //230
//$Test->setFixedScale(-2, 8);
$Test->setFontProperties('./pChart/Fonts/tahoma.ttf', 12);
$Test->setGraphArea(90, 40, 585, 380);
$Test->drawFilledRoundedRectangle(7, 7, 613, 423, 5, 240, 240, 240);
$Test->drawRoundedRectangle(5, 5, 615, 425, 5, 230, 230, 230);
$Test->drawGraphArea(255, 255, 255, TRUE);
$Test->drawScale($DataSet->GetData(), $DataSet->GetDataDescription(), ($log ? SCALE_LOG : SCALE_NORMAL), 150, 150, 150, TRUE, 0, 2, $WithMargin=FALSE,$SkipLabels=$twidth, False);
$Test->drawGrid(4, TRUE, 230, 230, 230, 50, $twidth);
$Test->setLineStyle(2);
$Test->drawLineGraph($DataSet->GetData(),$DataSet->GetDataDescription());
$Test->drawPlotGraph($DataSet->GetData(),$DataSet->GetDataDescription(),3,2,255,255,255);

// Finish the graph
//$Test->setFontProperties('./pChart/Fonts/tahoma.ttf', 8);
//$Test->drawLegend(600, 30, $DataSet->GetDataDescription(), 255, 255, 255);
$Test->setFontProperties('./pChart/Fonts/tahoma.ttf', 15);
$Test->drawTitle(50, 32, 'User contributions for ' . $user, 100, 160, 200, 585);
$FileName = strtolower($user);
$FileName = str_replace(" ", "_", $FileName);
$FileName = preg_replace('/[^a-z0-9_]/', '', $FileName);
$FileName = './tmp/xContribs.' . $FileName . '.png';
$Test->Render($FileName);
?>
