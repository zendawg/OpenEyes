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

  /**
   *
   * @var arary an associative array of varName => array() values, where varName
   * reflects the names of (javascript) variables configured per set of points;
   * the variable must match the variable name specified in addPlots(). The
   * array of points should be an associative array of the index on the chart
   * (0, ..., n) and the point data per index.
   */
  private $plots = array();
  /** The array of plot information per line set; is an associative array of
   * (javascript) variable name to an associative array of values for
   * configuring the points (e.g. label, points, lines etc.). */
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

  /**
   * Registers the script for this class.
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

  /**
   * Add a single set of plots (can be called several times).
   * 
   * @param string $var_name the javscript name of the plots.
   * @param array $data the array of points to lot, should be as an associative
   * array of x/y values.
   * @param string $label the label for the legend of the plots (displayed on
   * the graph).
   * @param bool $showLines join points up with lines?
   * @param bool $showPoints show points?
   */
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
   * Set the y axis data.
   * 
   * @param array $data an array of data values for the labels.
   * @param bool $timedata true if the elements are dates; false otherwise.
   */
  function setYAxisLabels($data, $timedata = false) {
    $this->timeData = $timedata;
    $this->yAxisLabels = $data;
  }

  /**
   * Set the X axis data.
   * 
   * @param array $data an array of data values for the labels.
   */
  function setXAxisLabels($data) {
    $this->xAxisLabels = $data;
  }

  /**
   * Display information about a point when the mouse hovers over it.
   * 
   * @param string $div_id the tooltip ID name for displaying the hover data.
   * @param int $top offset (y) from the mouse pointer to set the tooltip.
   * @param int $left offset (x) from the mouse pointer to set the tooltip.
   * @param int $borderPx The pixel border size of the tooltip.
   * @param int $borderStyle tooltip border style.
   * @param string $borderColour colur of the tooltip border as a hex triplet.
   * @param int $paddingPx padding pixel width.
   * @param string $bgColour colour of the background tooltip as a hex triplet.
   * @param float $opacity Opacity of the background of the tooltip.
   * @param bool $fadeIn fade in? True or false.
   * @param string $hoverDataDivId the div ID for the hover data.
   * @param string $hoverDataLabel label for prefixing text to the hover
   * output value.
   */
  function addHover($div_id = 'tooltip', $top = -25, $left = 5, $borderPx = 1, 
          $borderStyle = 'solid', $borderColour = 'fdd', $paddingPx = 2, 
          $bgColour = 'fee', $opacity = 0.80, $fadeIn = 200, 
          $hoverDataDivId = 'hoverdata', $hoverDataLabel = "Value: ") {
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

  /**
   * Pushes an element on to the array as a key/value pair.
   * 
   * @param array $array
   * @param object $key
   * @param object $value
   * @return type array the returned array after pushing the associative
   * array on to $array.
   */
  public function array_push_assoc($array, $key, $value) {
    $array[$key] = $value;
    return $array;
  }

  /**
   * Get the script text for creating the chart.
   * 
   * @param string $divName the name to give to the DIV that the chart will
   * reference.
   * @return string the chart, not including ensclosing 'script' tags.
   */
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
