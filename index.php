<!DOCTYPE html>
<html>
<head>
	<title>Chat</title>
	<meta charset="UTF-8">
	<link href="css/bootstrap.min.css" rel="stylesheet" type="text/css">
	<link href="css/font-awesome.min.css" rel="stylesheet" type="text/css">
	<script src="js/jquery-3.1.1.min.js"></script>
	<script src="js/bootstrap.min.js"></script>
	<script src="js/linkify.min.js"></script>
	<script src="js/linkify-html.min.js"></script>
	<script src="js/linkify-jquery.min.js"></script>
	<script src="js/linkify-plugin-hashtag.min.js"></script>
	<script src="js/linkify-plugin-mention.min.js"></script>
	<script src="js/linkify-string.min.js"></script>
	<script src="js/ion.sound.min.js"></script>
	<script src="js/showdown.min.js"></script>
	<script>
	ion.sound({
	    sounds: [
	        {name: "send"},
	        {name: "receive"},
	        {name: "error"},
	    ],
	    // main config
	    path: "sounds/",
	    preload: true,
	    multiplay: true,
	    volume: 0.8
	});
	</script>
	<script src="js/md5.js"></script>
	<script src="js/aes-js.min.js"></script>
	<style type="text/css">
		body{
			background: url('background.jpg') repeat;
		}

		output{
			position: absolute;
			bottom: 0;
			width: 100%;
			padding: 15px;
			margin-bottom: 40px;
			background: rgba(255,255,255,0.1);
		}

		output p{
			padding: 0;
			margin: 0;
			line-height: 1;
		}

		output p > b{
			margin-right: 10px;
		}

		form{
			position: absolute;
			bottom: 0;
		}
	</style>
</head>
<body>
<output></output>
<form id="chat">
	<div class="input-group">
		<input type="text" name="message" class="form-control" autocomplete="off">
		<span class="input-group-btn">
			<button type="submit" class="btn btn-xs btn-primary"><i class="fa fa-send"></i></button>
		</span>
	</div>
</form>
<div id="login" class="modal fade">
  <div class="modal-dialog" role="document">
	<div class="modal-content">
	  <div class="modal-header">
		<h4 class="modal-title">Chat</h4>
	  </div>
	  <div class="modal-body">
		<p>Escribe tu nombre de usuario y la contraseña del chat.</p>
		<div class="input-group">
			<span class="input-group-addon"><i class="fa fa-user"></i></span>
			<input type="text" name="username" class="form-control" autofocus="autofocus">
		</div>
		<div class="input-group">
			<span class="input-group-addon"><i class="fa fa-unlock-alt"></i></span>
			<input type="password" name="password" class="form-control">
		</div>
	  </div>
	  <div class="modal-footer">
		<button type="button" id="login-button" class="btn btn-primary">Guardar</button>
	  </div>
	</div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->
</body>
<script type="text/javascript">

var username = null;
var key = null;
var etag = null;
var kdect = "|||";
var last_timestamp = null;
var timerload;
var delay = 1500;

function cleaninput(){
	$("input[name=message]").focus();
	return document.querySelector("input[name=message]").value = "";
}

function addmsg(user, msj){
	msj = msj.linkify();
	var md = new showdown.Converter();
	msj = md.makeHtml(msj);
	msj = msj.substring(3, msj.length - 4); // remove <p> TAG.

	$("output").append("<p><b>" + user + "</b>" + "<span>" + msj + "</span></p>");
}

function cleanmsg(){
	$("output").empty();
}

function decode(msjenc){
	var t = msjenc.split(".");
	var message = [];
	for(i = 0; i < t[0].length; i = i+2){
		var s = "" + t[0][i] + t[0][i+1];
		message.push( parseInt(s, 16) );
	}

	// console.log(message);

	var iv = t[1];
	iv = aesjs.util.convertStringToBytes(iv);
	// console.log("la clave es: " + key);
	var AES = new aesjs.ModeOfOperation.cbc(key, iv);

	var b = AES.decrypt(message);
	return aesjs.util.convertBytesToString(b);
}

function newmessages(){
    $.ajax({
        type: "HEAD",
        async: true,
        url: 'messages.json?' + new Date().getTime(),
    }).done(function(message,text,jqXHR){
    	var tag = jqXHR.getResponseHeader("ETag");
    	if(etag != tag){
    		etag = tag;
    		console.log("nuevos mensajes");
    		// ion.sound.play('receive');
    		loadmsg();
    	}
    	setTimeout(function(){ newmessages() }, delay);
    });
}

function loadmsg(){
	$.ajax({
		dataType: 'json',
		url: 'messages.json?' + new Date().getTime(),
	}).done(function(r){
		// cleanmsg();
		$.each(r, function(i){
			var m = decode(r[i]);
			if(m.substring(0, 3) == kdect){
				data = m.substring(3).split("|");
				if(data[1] > last_timestamp){
					last_timestamp = data[1];
					if(data[0] != username){
						addmsg(data[0], decodeURIComponent(data[2]));
					}
				}
			}
		});
	});
}

$(function(){
	$("#login").modal({
		backdrop: 'static',
		keyboard: false
	});
	$("input[name=password]").keypress(function(e){
		if(e.which == 13){
			$("#login-button").trigger('click');
		}
	});
	$("html").keyup(function(e){
		if(e.which == 27){
			$("input[name=message]").focus().select();
		}
	});
	$("#login-button").click(function(){
		$("#login.modal .input-group").each(function(){
			$(this).removeClass("has-danger");
		});
		var u = $.trim($("input[name=username]").val());
		var k = $.trim($("input[name=password]").val());

		if(u == ""){ $("input[name=username]").parent().addClass("has-danger"); }
		if(k == ""){ $("input[name=password]").parent().addClass("has-danger"); }
		if(u == "" || k == ""){ return; }

		username = u;
		key = md5(k);
		key = aesjs.util.convertStringToBytes(key);

		$("#login").modal('toggle');
		$("input[name=message]").focus();

		timerload = setTimeout(function(){ newmessages() }, 100);
	});

	$("form#chat").submit(function(e){
		e.preventDefault();

		var msj = $.trim($("input[name=message]").val());
		if(msj == ""){ return; }
		// ------------
		// https://www.sitepoint.com/community/t/encode-accented-letters-in-utf-8-charset/22968/2
		// ------------
		text = kdect + username + "|" + Math.floor(Date.now()) + "|" + encodeURIComponent(msj);
		while(text.length % 16 != 0){ text = text + " "; }
		var msb = aesjs.util.convertStringToBytes(text);

		var ivp = md5(Math.random() * 100000000).substring(0, 16);
		var iv = aesjs.util.convertStringToBytes(ivp);

		var AES = new aesjs.ModeOfOperation.cbc(key, iv);
		var enc = AES.encrypt(msb);

		var hexenc = "";
		$.each(enc, function(i){
			var r = enc[i].toString(16);
			if(r.length == 1){ r = "0" + r; }
			hexenc = hexenc + r;
		});

		var send = (hexenc + "." + ivp);
		// console.log(send);

		$.ajax({
			data: {message: send},
			dataType: 'json',
			url: 'save.php',
			method: 'POST'
		}).done(function(ret){
			addmsg(username, msj);
			ion.sound.play('send');
			cleaninput();
		}).fail(function(ret){
			ion.sound.play('error');
			alert("Error: \n" + ret);
			console.log(ret);
		});
	});
});
</script>
</html>