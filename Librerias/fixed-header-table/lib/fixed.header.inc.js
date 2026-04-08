$(document).ready(function() {
    $('.fixedHeader01').fixedHeaderTable({ height: '250', footer: false, cloneHeadToFoot: true, altClass: 'odd', themeClass: 'fancyTable', autoShow: false });    
    $('.fixedHeader01').fixedHeaderTable('show', 1000);
    
    $('.fixedHeader02').fixedHeaderTable({ height: '250', footer: true, altClass: 'odd', themeClass: 'fancyTable' });
    
    $('.fixedHeader03').fixedHeaderTable({ height: '400', altClass: 'odd', footer: true, fixedColumn: true, themeClass: 'fancyTable' });
    
    $('.fixedHeader04').fixedHeaderTable({ height: '400', altClass: 'odd', footer: true, cloneHeadToFoot: true, fixedColumns: 3, themeClass: 'fancyTable' });

    $('.fixedHeader05').fixedHeaderTable({ height: '400', altClass: 'odd', footer: false, cloneHeadToFoot: true, fixedColumns: 2, themeClass: 'fancyTable', autoResize: true });

    $('.fixedHeader06').fixedHeaderTable({ height: '110', footer: false, cloneHeadToFoot: true, altClass: 'odd', themeClass: 'fancyTable', autoShow: false });    
    $('.fixedHeader06').fixedHeaderTable('show', 1000);
	
});