function addConfig()
{
	var tbody = document.getElementById('configs');
	var tr = document.createElement('TR');
	var td = document.createElement('TD');
	var input = document.createElement('INPUT');
	input.type = 'text';
	input.name = 'config_key[]';
	td.appendChild(input);
	tr.appendChild(td);
	td = document.createElement('TD');
	input = document.createElement('INPUT');
	input.type = 'text';
	input.name = 'config_value[]';
	td.appendChild(input);
	tr.appendChild(td);
	td = document.createElement('TD');
	td.innerHTML = "<a title=\"\" onclick=\"delRow(this); return false;\" href=\"#\"><img class=\"link\" alt=\"\" src=\"http://www.bus-it.com/busit/images/icons/small/close.png\" /></a>";
	tr.appendChild(td);
	tbody.appendChild(tr);
}

function delRow(e)
{
	while( e.parentNode && e.nodeName.toUpperCase() != 'TR' )
		e = e.parentNode;
	if( e.nodeName.toUpperCase() == 'TR' )
		e.parentNode.removeChild(e);
	
	setTimeout(invalidateList, 1);
}

function addInterface()
{
	var tbody = document.getElementById('interfaces');
	var tr = document.createElement('TR');
	var td = document.createElement('TD');
	var input = document.createElement('INPUT');
	td.innerHTML = "<a title=\"\" onclick=\"switchInterface(this); return false;\" href=\"#\"><img class=\"link\" alt=\"\" src=\"/ext/refresh3.png\" /></a>" + 
		"<input type=\"hidden\" name=\"int_dir[]\" value=\"INPUT\" /><span>INPUT</span>";
	tr.appendChild(td);
	td = document.createElement('TD');
	input.type = 'text';
	input.name = 'int_key[]';
	input.onkeyup = function() { invalidateList(); }
	input.onblur = function() { invalidateList(); }
	td.appendChild(input);
	tr.appendChild(td);
	td = document.createElement('TD');
	input = document.createElement('INPUT');
	input.type = 'text';
	input.name = 'int_value[]';
	td.appendChild(input);
	tr.appendChild(td);
	td = document.createElement('TD');
	td.innerHTML = "<a title=\"\" onclick=\"delRow(this); return false;\" href=\"#\"><img class=\"link\" alt=\"\" src=\"http://www.bus-it.com/busit/images/icons/small/close.png\" /></a>";
	tr.appendChild(td);
	tbody.appendChild(tr);
	
	setTimeout(invalidateList, 1);
}

function switchInterface(e)
{
	while( e.nextSibling )
	{
		e = e.nextSibling;
		if( e.nodeName.toUpperCase() == 'INPUT' )
			e.value = (e.value == 'INPUT' ? 'OUTPUT' : 'INPUT');
		if( e.nodeName.toUpperCase() == 'SPAN' )
			e.innerHTML = (e.innerHTML == 'INPUT' ? 'OUTPUT' : 'INPUT');
	}
	
	setTimeout(invalidateList, 1);
}

function invalidateList()
{
	var push = document.getElementById('push');
	var pull = document.getElementById('pull');
	
	while( push.options.length > 1 ) push.remove(1);
	while( pull.options.length > 1 ) pull.remove(1);
	
	var table = document.getElementById('interfaces');
	for( var i = 1; i < table.rows.length; i++ )
	{
		var type = table.rows[i].cells[0].getElementsByTagName('INPUT')[0].value;
		var key = table.rows[i].cells[1].getElementsByTagName('INPUT')[0].value;
		
		if( key.length == 0 )
			continue;
			
		var option = document.createElement('OPTION');
		option.text = key;
		option.value = key;
		
		if( type == 'INPUT' )
			push.add(option);
		else
			pull.add(option);
	}
}