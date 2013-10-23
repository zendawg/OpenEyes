<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of FlotChart
 *
 * @author rich
 */
class FlotChart implements JScriptChart {

  private $plots = array();
  private $plotData = array();
  private $xAxisZoomRange = null;
  private $yAxisZoomRange = null;
  private $xAxisPanRange = null;
  private $yAxisPanRange = null;
  private $xAxisLabels = null;
  private $yAxisLabels = null;
  private $timeData = false;
  private $hover = false;
  private $hoverText;
  private $divName = null;

  /**
   * 
   * @param type $scriptName
   */
  public function registerScript($scriptName) {
    Yii::app()->clientScript->registerScriptFile($scriptName);
  }
  
  public function addPanXAxis($min, $max) {
    $this->xAxisPanRange = array($min, $max);
  }

  public function addPanYAxis($min, $max) {
    $this->yAxisPanRange = array($min, $max);
  }

  public function addZoomXAxis($min, $max) {
    $this->xAxisZoomRange = array($min, $max);
  }

  public function addZoomYAxis($min, $max) {
    $this->yAxisZoomRange = array($min, $max);
  }

  public function addPlots($var_name, $data, $label, $showLines = true, $showPoints = true) {
    $this->plots = $this->array_push_assoc($this->plots, $var_name, $data);
    $tmp = array();
    $tmp = $this->array_push_assoc($tmp, 'data', $var_name);
    $tmp = $this->array_push_assoc($tmp, 'label', $label);
    $tmp = $this->array_push_assoc($tmp, 'points', $showPoints);
    $tmp = $this->array_push_assoc($tmp, 'lines', $showLines);
    $this->plotData = $this->array_push_assoc($this->plotData, $var_name, $tmp);
  }

  /**
   * 
   * @param type $min
   * @param type $max
   */
  function setYAxisLabels($data, $timedata = false) {
    $this->timeData = $timedata;
    $this->yAxisLabels = $data;
  }

  /**
   * 
   * @param type $min
   * @param type $max
   */
  function setXAxisLabels($data) {
    $this->xAxisLabels = $data;
  }

  /**
   * 
   * @param type $div_id
   * @param type $top
   * @param type $left
   * @param type $borderPx
   * @param type $borderStyle
   * @param type $borderColour
   * @param type $paddingPx
   * @param type $bgColour
   * @param type $opacity
   * @param type $fadeIn
   * @param type $hoverDataDivId
   * @param type $hoverDataLabel
   */
  function addHover($div_id = 'tooltip', $top = -25, $left = 5, $borderPx = 1, $borderStyle = 'solid', $borderColour = 'fdd', $paddingPx = 2, $bgColour = 'fee', $opacity = 0.80, $fadeIn = 200, $hoverDataDivId = 'hoverdata', $hoverDataLabel = "Value: ") {
    $this->hover = true;

    $this->hoverText = "function showTooltip(x, y, contents) {"
            . "$(\"<div id='" . $div_id . "'>\" + contents + \"</div>\").css({"
            . "position: \"absolute\","
            . "display: \"none\","
            . "top: y + " . $top . ","
            . "left: x + " . $left . ","
            . "border: \"" . $borderPx . "px " . $borderStyle . " #" . $borderColour . "\","
            . "padding: \"" . $paddingPx . "px\","
            . "\"background-color\": \"#" . $bgColour . "\","
            . "opacity: " . $opacity
            . "}).appendTo(\"body\").fadeIn(" . $fadeIn . ");"
            . "}"
            . "var previousPoint = null;"
            . "$(\"#placeholder\").bind(\"plothover\", function (event, pos, item) {"
            . "if (item) {"
            . "if (previousPoint != item.dataIndex) {"
            . "previousPoint = item.dataIndex;"
            . "var str = \"" . $hoverDataLabel . "\"+ item.datapoint[1].toFixed(2);"
            . "$(\"#" . $hoverDataDivId . "\").text(str);"
            . "$(\"#" . $div_id . "\").remove();"
            . "var x = item.datapoint[0].toFixed(2),"
            . "y = item.datapoint[1].toFixed(2);"
            . "showTooltip(item.pageX, item.pageY, y);"
            . "}"
            . "} else {"
            . "$(\"#" . $div_id . "\").remove();"
            . "previousPoint = null;            "
            . "}"
            . "});";
  }

  public function array_push_assoc($array, $key, $value) {
    $array[$key] = $value;
    return $array;
  }

  public function toString($divName) {

    $data = "\$(function(){\n";

    // add the individual data points:
    foreach ($this->plotData as $name => $plot) {
      $data .= "\n\tvar " . $name . " = [];";
    }
    foreach ($this->plots as $name => $plot) {
      foreach ($plot as $x => $y) {
        $data .= "\n\t" . $name . ".push([" . $x . ", " . $y . "]);";
      }
    }
    $data .= "\n\tvar placeholder=\$(\"#placeholder\");";
    // next add data about the individual lines:
    $data .= "\n\tvar plot = $.plot(" . $divName
            . ", [";
    foreach ($this->plotData as $name => $plot) {
      $data .= "\n{";
      $data .= "\n\tdata: " . $name
              . "\n\t\t, label: \"" . $plot["label"] . "\""
              . "\n\t\t, lines: { show : " . var_export($plot['lines'], true) . "}"
              . "\n\t\t, points: { show : " . var_export($plot['points'], true) . "}";
      $data .= "\n\t},";
    }
    $data = substr($data, 0, -1) . "\n],{";
    if ($this->hover) {
      $data .= "grid: {"
              . "hoverable: true"
              . "},";
    }
    // information about the zoom and pan:
    if ($this->xAxisZoomRange && $this->yAxisZoomRange) {
      $data .= "\n\tzoom: { interactive: true },";
    }
    if ($this->xAxisPanRange && $this->yAxisPanRange) {
      $data .= "\n\tpan: { interactive: true },";
    }
    // next, deal with axis:
    // Y:
    $data .= "\n\tyaxis: {";
    if ($this->yAxisZoomRange && $this->yAxisPanRange) {
      $data .= "\n\t\tzoomRange: [" . $this->yAxisZoomRange[0] . ", " . $this->yAxisZoomRange[1] . "],";
      $data .= "\n\t\tpanRange: [" . $this->yAxisPanRange[0] . ", " . $this->yAxisPanRange[1] . "],";
    }
    if ($this->yAxisLabels) {
      $data .= "\n\tticks: [";
      foreach ($this->yAxisLabels as $key => $value) {
        $data .= "\n\t[" . $key . ", '" . $value . "'],";
      }
//      $data = substr($data, 0, -1);
      $data .= "\n\t]";
    }
    $data .= "\n\t},";
    // X:
    $data .= "\n\txaxis: {";
    if ($this->xAxisZoomRange && $this->xAxisPanRange) {
      $data .= "\n\t\tzoomRange: [" . $this->xAxisZoomRange[0] . ", " . $this->xAxisZoomRange[1] . "],";
      $data .= "\n\t\tpanRange: [" . $this->xAxisPanRange[0] . ", " . $this->xAxisPanRange[1] . "],";
    }
    if ($this->xAxisLabels) {
      $data .= "\n\tticks: [";
      foreach ($this->xAxisLabels as $key => $value) {
        $data .= "\n\t[" . $key . ", \"" . $value . "\"],";
      }
//      $data = substr($data, 0, -1);
      $data .= "\n\t]";
    }
    $data .= "\n\t}";
    $data .= "\n});";

    // custom functions:
    if ($this->hover) {
      $data .= $this->hoverText;
    }

    $data .= "\n});";

    return $data;
  }

}

?>
