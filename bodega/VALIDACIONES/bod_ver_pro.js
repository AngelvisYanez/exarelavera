(function(){
	let ver_pro={
		Bod_Cod:$('#Bod_Cod')
	}

	ver_pro.cargarBodegas=function(){
		return new Promise((reso,rej)=>{
			$.getDataJson('',{'bodSuc':true},function(res){
				reso(res.data)
			},function(){
				rej(res)
			})
		})
	}


	ver_pro.changeBodega=function(id_Bodega){
		$('#searchGrid').Search('#searchProducto','searchProductos');
	}

	ver_pro.showChildGrid=function(parentRowID, parentRowKey){
		var childGridID = parentRowID + "_table";
            var childGridPagerID = parentRowID + "_pager";
            // send the parent row primary key to the server so that we know which grid to show
            var childGridURL = name_file+"?ajaxSubgrid="+parentRowKey+"&Bod_Cod="+$('#Bod_Cod').val();
            //childGridURL = childGridURL + "&parentRowID=" + encodeURIComponent(parentRowKey)

            // add a table and pager HTML elements to the parent grid row - we will render the child grid here
            $('#' + parentRowID).append('<table id=' + childGridID + '></table><div id=' + childGridPagerID + ' class=scroll></div>');
            $("#" + childGridID).jqGrid({
                url: childGridURL,
                mtype: "GET",
                datatype: "json",
                page: 1,
                height: 270,
                colModel: [
                    { label: 'Aju.', name: 'Aju_Cod',key: true, sortable: false },
                    { label: 'Mov.', name: 'Mov_Cod' ,hidden:true  },
                    { label: 'Mov.', name: 'Aju_Fec', sortable: false  },
                    { label: 'Tipo', name: 'tipo' ,align: "center",formatter:function(c,op,row){return c==='I'?'Entrada':'Salida'}, sortable: false},
                    { label: 'Bodega', name: 'Bod_Nom',align: "center" , sortable: false},
                    { label: 'Salida', name: 'Kar_Sal',hidden:true},
                    { label: 'Ingreso', name: 'Kar_Can',hidden:true },
                    { label: 'Estado', name: 'Mov_Est', formatter: function(cv, opts, rObj){ return cv==='F'?'Finalizado':cv==='A'?'En Espera':'--'; } ,align: "center", sortable: false},
                    { label: 'Cantidad', name: 'cantidad', formatter:function(c,op,row){
                    	let val=row.Kar_Can*1-row.Kar_Sal*1;
                    	let color=(val>0?'green':'red');
                    	return `<span style="color:${color};">${Math.abs(val)}</span>`;
                    },align: "right", sortable: false }
                ],loadComplete:function(data){
                	console.log(data);
                	data.rows.map(ind=>{
                		if(ind.Mov_Est==='A'){
                			$(`#${childGridID} tr#${ind.Aju_Cod}`).addClass('cellRed2');
                		}
                	});
                },
				loadonce: true,
                width: 500,
                height: '100%'
            });
	}



	$('#searchGrid').createGrid({
		caption: 'Resultado de la B&uacute;squeda', height: 270, datatype: "local",
		colModel: [
	  	{label: 'C&oacute;d. Int.', name: 'Pro_Cod', width: 30, align: "center", key: true},
	  	{label: 'Uni.', name: 'Uni_Des', align: "center",width: 40},
	  	{label: 'Descripci&oacute;n', name: 'Ite_Lar', width: 50, align: "center" },
	  	{label: 'Categor&iacute;a', name: 'Cat_Des', width: 100, align: "center"},
	  	{label: 'Stock', name: 'cantidad', width: 50, align: "center"},
	  	{label: 'Destino', name: 'Bod_Des', width: 120, align: "center", hidden:true}
		],
		subGrid:true,
		loadComplete: function(data){

		},
		subGridRowExpanded: ver_pro.showChildGrid, // javascript function that will take care of showing the child grid
		isHasSubGrid : function (rowid) {
		// if custommerid begin with B, do not use subgrid
			var cell = $(this).jqGrid('getCell', rowid, 1);
			//console.log(cell, rowid);
			if( cell && cell.substring(0,1) === "B") {
				return false;
			}
			return true;
			},
			  subGridOptions : {
			// configure the icons from theme rolloer
				plusicon: "ui-icon-triangle-1-e",
				minusicon: "ui-icon-triangle-1-s",
				openicon: "ui-icon-arrowreturn-1-e"
			}
	}, true, '#searchGridPager', {refresh: true});

	/**
	 * inicializa interfaz
	 */
	
	$('#Bod_Cod').on('change',function(e){
		ver_pro.changeBodega($('#Bod_Cod').val());
	});



	let opt=$('<option></option>').text('Todas').val(0);
	$('#Bod_Cod').append(opt);	
	ver_pro.cargarBodegas().then(data=>data.map((val,ind,arr)=>{
		let opt_bod=$('<option></option>').text(val.Bod_Nom).val(val.Bod_Cod).data(val);
		$('#Bod_Cod').append(opt_bod);		
	})).catch(err=>console.log(err));




})()