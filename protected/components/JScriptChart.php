<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of JScriptChart
 *
 * @author rich
 */
interface JScriptChart {
  
  /**
   * 
   * @param type $var_name
   * @param type $data
   * @param type $label
   * @param type $showLines
   * @param type $showPoints
   */
  function addPlots($var_name, $data, $label, $showLines=true, $showPoints=true);
  
  /**
   * 
   * @param type $min
   * @param type $max
   */
  function setYAxisLabels($data);
  
  /**
   * 
   * @param type $min
   * @param type $max
   */
  function setXAxisLabels($data);
  /**
   * 
   * @param type $min
   * @param type $max
   */
  function addZoomXAxis($min, $max);
  
  /**
   * 
   * @param type $min
   * @param type $max
   */
  function addZoomYAxis($min, $max);
  
  /**
   * 
   * @param type $min
   * @param type $max
   */
  function addPanXAxis($min, $max);
  
  /**
   * 
   * @param type $min
   * @param type $max
   */
  function addPanYAxis($min, $max);
  
  /**
   * 
   */
  function addHover();
  
  function registerScript($scriptName);
  
  /**
   * 
   * @param type $divName
   */
  function toString($divName);
}

?>
