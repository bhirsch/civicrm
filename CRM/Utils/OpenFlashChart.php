<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 3.1                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2010                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2010
 * $Id$
 *
 */

require_once 'packages/OpenFlashChart/php-ofc-library/open-flash-chart.php';

/**
 * Build various graphs using Open Flash Chart library.
 */

class CRM_Utils_OpenFlashChart
{
    /**
     * colours.
     * @var array
     * @static
     */
    private static $_colours = array( "#C3CC38", "#C8B935", "#CEA632", "#D3932F", 
                                      "#D9802C", "#FA6900", "#DC9B57", "#F78F01", 
                                      "#5AB56E", "#6F8069", "#C92200", "#EB6C5C"
                                      );
    
    /**
     * Build The Bar Gharph.
     *
     * @param  array  $params  assoc array of name/value pairs          
     *
     * @return object $chart   object of open flash chart.
     * @static
     */
    static function &barChart( &$params )
    {
        $chart = null;
        if ( empty( $params ) ) {
            return $chart;
        }
        
        $values = CRM_Utils_Array::value( 'values', $params );
        if ( !is_array( $values ) || empty( $values ) ) return $chart; 
        
        // get the required data.
        $xValues = $yValues = array( );
        foreach ( $values as $xVal => $yVal ) {
            $yValues[] = (double)$yVal;
            
            // we has to have x values as string.
            $xValues[] = (string)$xVal;
        }
        $chartTitle = CRM_Utils_Array::value( 'legend', $params ) ? $params['legend'] : ts( 'Bar Chart' );
        
        //set y axis parameters.
        $yMin = 0;
        
        // calculate max scale for graph.
        $yMax = max( $yValues );
        if ( $mod = $yMax%(str_pad( 5, strlen($yMax)-1, 0))) { 
            $yMax += str_pad( 5, strlen($yMax)-1, 0)-$mod;
        }
        $ySteps = $yMax/5;
        
        // $bar = new bar( );
        // glass seem to be more cool 
        $bar = new bar_glass();
        
        //set values.
        $bar->set_values( $yValues );
        
        // call user define function to handle on click event.
        if ( $onClickFunName = CRM_Utils_Array::value( 'on_click_fun_name', $params ) ) {
            $bar->set_on_click( $onClickFunName );
        }
        
        // get the currency.
        require_once 'CRM/Utils/Money.php';
        $config   =& CRM_Core_Config::singleton();
        $symbol   = $config->defaultCurrencySymbol;
                        
        // set the tooltip.
        $bar->set_tooltip( "$symbol #val#" );
        
        // create x axis label obj.
        $xLabels = new x_axis_labels( );
        $xLabels->set_labels( $xValues );

        // set angle for labels.
        if ( $xLabelAngle = CRM_Utils_Array::value( 'xLabelAngle', $params ) ) {
            $xLabels->rotate( $xLabelAngle );
        }
        
        // create x axis obj.
        $xAxis = new x_axis( );
        $xAxis->set_labels( $xLabels );
        
        //create y axis and set range. 
        $yAxis = new y_axis( );
        $yAxis->set_range( $yMin, $yMax, $ySteps );
        
        // create chart title obj.
        $title = new title( $chartTitle );
        
        // create chart.
        $chart = new open_flash_chart();
        
        // add x axis w/ labels to chart.
        $chart->set_x_axis( $xAxis );
        
        // add y axis values to chart.
        $chart->add_y_axis( $yAxis );
        
        // set title to chart.
        $chart->set_title( $title );
        
        // add bar element to chart.
        $chart->add_element( $bar );

        // add x axis legend.
        if ( $xName = CRM_Utils_Array::value('xname', $params ) ) {
            $xLegend = new x_legend( $xName );
            $xLegend->set_style( "{font-size: 13px; color:#000000; font-family: Verdana; text-align: center;}" );
            $chart->set_x_legend( $xLegend );
        }
        
        // add y axis legend.
        if ( $yName = CRM_Utils_Array::value( 'yname', $params ) ) {
            $yLegend = new y_legend( $yName );
            $yLegend->set_style( "{font-size: 13px; color:#000000; font-family: Verdana; text-align: center;}" );
            $chart->set_y_legend( $yLegend );
        }
        
        return $chart;
    }
    
    /**
     * Build The Pie Gharph.
     *
     * @param  array  $params  assoc array of name/value pairs          
     *
     * @return object $chart   object of open flash chart.
     * @static
     */
    static function &pieChart( &$params )
    {
        $chart = null;
        if ( empty( $params ) ) {
            return $chart;
        }
        $allValues = CRM_Utils_Array::value( 'values', $params );
        if ( !is_array( $allValues ) || empty( $allValues ) ) return $chart; 
        
        // get the required data.
        $values = array( );
        foreach ( $allValues as $label => $value ) {
            $values[] = new pie_value( (double)$value, $label );
        }
        $graphTitle = CRM_Utils_Array::value( 'legend', $params ) ? $params['legend'] : ts( 'Pie Chart' );
        
        //get the currency.
        require_once 'CRM/Utils/Money.php';
        $config   =& CRM_Core_Config::singleton();
        $symbol   = $config->defaultCurrencySymbol;
        
        $pie = new pie();
        $pie->radius( 100 );
        
        // call user define function to handle on click event.
        if ( $onClickFunName = CRM_Utils_Array::value( 'on_click_fun_name', $params ) ) {
            $pie->on_click( $onClickFunName );
        }
        
        $pie->set_start_angle( 35 );
        $pie->add_animation( new pie_fade( ) );
        $pie->add_animation( new pie_bounce( 2 ) );
        
        // set the tooltip.
        $pie->set_tooltip( "Amount is $symbol #val# of $symbol #total# <br>#percent#" );
        
        // set colours.
        $pie->set_colours( self::$_colours );
        
        $pie->set_values( $values );
        
        //create chart.
        $chart = new open_flash_chart();
        
        // create chart title obj.
        $title = new title( $graphTitle );
        $chart->set_title( $title );
        
        $chart->add_element( $pie );
        $chart->x_axis = null;
        
        return $chart;
    }
    
    static function chart( $rows, $chart, $interval ) 
    {
        $chartData = array( );
        
        switch ( $interval ) {
        case 'Month' :
            foreach ( $rows['receive_date'] as $key => $val ) {
                list( $year, $month ) = explode( '-', $val );
                $graph[substr($rows['Month'][$key],0,3) .' '. $year ] = $rows['value'][$key];
            }
            
            $chartData = array('values' => $graph,
                               'legend' => ts('Monthly Contribution Summary') );
            break;
            
        case 'Quarter' :
            foreach ( $rows['receive_date'] as $key => $val ) {
                list( $year, $month ) = explode( '-', $val );
                $graph['Quarter '. $rows['Quarter'][$key] .' of '. $year ] = $rows['value'][$key];
            }
            
            $chartData = array('values' => $graph,
                               'legend' => ts('Quarterly Contribution Summary') );
            break;
            
        case 'Week' :
            foreach ( $rows['receive_date'] as $key => $val ) {
                list( $year, $month ) = explode( '-', $val );
                $graph['Week '. $rows['Week'][$key] .' of '. $year ] = $rows['value'][$key];
            }
            
            $chartData = array('values' => $graph,
                               'legend' => ts('Weekly Contribution Summary') );
            break;
            
        case 'Year' :
            foreach ( $rows['receive_date'] as $key => $val ) {
                list( $year, $month ) = explode( '-', $val );
                $graph[$year] = $rows['value'][$key];
                
            }
            $chartData = array('values' => $graph,
                               'legend' => ts('Yearly Contribution Summary') );
            break;
            
        }
        
        // rotate the x labels.
        $chartData['xLabelAngle'] = CRM_Utils_Array::value( 'xLabelAngle', $rows, 20 );
        //legend
        $chartData['xname']       = CRM_Utils_Array::value( 'xname', $rows );
        $chartData['yname']       = CRM_Utils_Array::value( 'yname', $rows );
        
        // carry some chart params if pass.
        foreach ( array( 'xSize', 'ySize', 'divName' ) as $f ) {
            if ( CRM_Utils_Array::value( $f, $rows ) ) {
                $chartData[$f] = $rows[$f];
            }
        }
        
        return self::buildChart( $chartData, $chart );
    }
    
    static function reportChart($rows, $chart, $interval, &$chartInfo ) 
    {
        foreach ( $interval as $key => $val ) {
            $graph[$val] = $rows['value'][$key];
        }
        
        $chartData = array( 'values' => $graph,
                            'legend' => $chartInfo['legend'],
                            'xname'  => $chartInfo['xname'],
                            'yname'  => $chartInfo['yname']
                            );
        
        // rotate the x labels.
        $chartData['xLabelAngle'] = CRM_Utils_Array::value( 'xLabelAngle',$chartInfo, 20 );
        
        // carry some chart params if pass.
        foreach ( array( 'xSize', 'ySize', 'divName' ) as $f ) {
            if ( CRM_Utils_Array::value( $f, $rows ) ) {
                $chartData[$f] = $rows[$f];
            }
        }
        
        return self::buildChart( $chartData, $chart );
    }
    
    function buildChart( &$params, $chart ) {
        $openFlashChart = array( );
        if ( $chart && is_array( $params ) && !empty( $params ) ) {
            require_once 'CRM/Utils/OpenFlashChart.php';
            // build the chart objects.
            eval( "\$chartObj = CRM_Utils_OpenFlashChart::" . $chart .'( $params );' );    
            
            $openFlashChart = array( );
            if ( $chartObj ) {
                // calculate chart size.
                $xSize = CRM_Utils_Array::value( 'xSize', $params, 400 );
                $ySize = CRM_Utils_Array::value( 'ySize', $params, 300 );
                if ( $chart == 'barChart' ) {
                    $ySize = CRM_Utils_Array::value( 'ySize', $params, 250 );
                    $xSize = 60*count( $params['values'] );
                    //hack to show tooltip.
                    if ( $xSize < 200 ) {
                        $xSize = (count( $params['values'] ) > 1) ? 100*count( $params['values'] ) : 170;
                    }
                }

                // generate unique id for this chart instance
                $uniqueId = md5( uniqid( rand( ), true ) );
                
                $openFlashChart["chart_{$uniqueId}"]['size']    = array( 'xSize' =>  $xSize, 'ySize' => $ySize );
                $openFlashChart["chart_{$uniqueId}"]['object']  = $chartObj;
                
                // assign chart data to template
                $template = CRM_Core_Smarty::singleton( );
                $template->assign( 'uniqueId', $uniqueId );
                $template->assign( "openFlashChartData", json_encode( $openFlashChart ) );
            }
        }
        
        return $openFlashChart;
    }
    
}
