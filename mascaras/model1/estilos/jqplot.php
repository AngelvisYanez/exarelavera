<?Php
/* 
Alias:	jqgrid5
Descripción: Agrupa los estilos del plugin jqplot
Fecha de Creacion:	2015-07-01
Desarrollador:	Erik Niebla
*/
?>				
		<link rel="stylesheet" type="text/css" media="screen" href="../../framework/jquery/jqplot/jquery.jqplot.min.css" />
	
		<script type="text/ecmascript" src="../../framework/jquery/jqplot/jquery.jqplot.min.js"></script> 
		<script type="text/ecmascript" src="../../framework/jquery/jqplot/plugins/jqplot.highlighter.min.js"></script>
		<script type="text/ecmascript" src="../../framework/jquery/jqplot/plugins/jqplot.barRenderer.min.js"></script> 
		<script type="text/ecmascript" src="../../framework/jquery/jqplot/plugins/jqplot.pieRenderer.min.js"></script> 
		<script type="text/ecmascript" src="../../framework/jquery/jqplot/plugins/jqplot.categoryAxisRenderer.min.js"></script> 
		<script type="text/ecmascript" src="../../framework/jquery/jqplot/plugins/jqplot.pointLabels.min.js"></script>
		<script type="text/ecmascript" src="../../framework/jquery/jqplot/plugins/jqplot.canvasTextRenderer.min.js"></script>
		<script type="text/ecmascript" src="../../framework/jquery/jqplot/plugins/jqplot.canvasAxisLabelRenderer.min.js"></script>
		<script type="text/ecmascript" src="../../framework/jquery/jqplot/plugins/jqplot.canvasAxisTickRenderer.min.js"></script>
		<style>.jqplot-canvasOverlay-tooltip, .jqplot-cursor-tooltip, .jqplot-highlighter-tooltip{z-index:99;background: rgba(120, 120, 120, 0.8);color: white;font-size:11px;}</style>
		<script>
			function tooltipContentEditorX(str, seriesIndex, pointIndex, plot) {
                return plot.axes.xaxis.ticks[pointIndex] + ", " + plot.data[seriesIndex][pointIndex];
            }
			function tooltipContentEditorY(str, seriesIndex, pointIndex, plot) {				
                return plot.axes.yaxis.ticks[pointIndex] + ", " + plot.data[seriesIndex][pointIndex];
            }
		</script>
		

		