<?php
/**
 * aTikit v1.0 by Core 3 Networks (www.core3networks.com)
 *
 * Copyright (c) 2013 Core 3 Networks, Inc and Chris Horne <chorne@core3networks.com>
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A
 * PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
 * COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
 * IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 * @package atikit10
 * @helper js
 */
		
class js
{
	
	static public function scrollBottom()
	{
		return "$('html, body').animate({ scrollTop: $(document).height() }, 500);";
	}
	
	static public function ajaxFile($bind, $code)
	{
		$data = "
		var errorHandler = function(event, id, fileName, reason) {
		qq.log('id: ' + id + ', fileName: ' + fileName + ', reason: ' + reason);
		};
	
	var fileNum = 0;
	
	$('#{$bind}').fineUploader({
		debug: true,
		request:
		{
		endpoint: '/upload.php',
		paramsInBody: true,
		params:
		{
		code: '{$code}'
			
		},
		fileNum: function()
		{
		fileNum+=1;
		return fileNum;
			
		}
		},
		
		chunking: {
		enabled: false
		},
		resume: {
		enabled: true
		},
		retry: {
		enableAuto: true,
		showButton: true
		}
		})
		.on('error', errorHandler)
		.on('uploadChunk resume', function(event, id, fileName, chunkData) {
		qq.log('on' + event.type + ' -  ID: ' + id + ', FILENAME: ' + fileName + ', PARTINDEX: ' + chunkData.partIndex + ', STARTBYTE: ' + chunkData.startByte + ', ENDBYTE: ' + chunkData.endByte + ', PARTCOUNT: ' + chunkData.totalParts);
	});
	
	
	";
	return $data;
	}
	
	
	static public function datatable($id, $records = 20)
	{
		$data = "$('#{$id}').dataTable( {
			sPaginationType: \"bootstrap\",
			\"iDisplayLength\": $records,
			oLanguage: {
				\"sLengthMenu\": \"_MENU_ records per page\"
				
			}
		});
		";
		return $data;
	}
	
	
	static public function alert($params)
	{
		
		$title = $params['title'];
		$body = $params['body'];
		$data = "create('default', { title: '$title', text:'$body'});";
        return $data;
	}
	
	static public function scrollTop($class)
	{
	
		$data = "$('.$class').click(function () {
		$('body,html').animate({
		scrollTop: 0
	}, 800);
	return false;
	});";
	
	return $data;
	}
		
	static public function maskInput($class, $format)
	{
		return "$('.$class').mask('$format');";
			
	}
	
	static public function stripeToken($key, $submitClass)
	{
		$data = "
		Stripe.setPublishableKey('$key');
		function stripeResponseHandler(status, response) 
		{
			if (response.error) 
			{
				// re-enable the submit button
				$('.$submitClass').removeAttr('disabled');
				// show the errors on the form
				$('.$submitClass').html('Update Billing Details');
				alert(response.error.message);
			} 
			else 
			{
				var form$ = $('#payment-form');
				var token = response['id'];
				form$.append(\"<input type='hidden' name='stripeToken' value='\" + token + \"' />\");
				form$.get(0).submit();
			}
		}
		$('.$submitClass').click(function(event) {
		$('.$submitClass').attr('disabled', 'disabled');
		$('.$submitClass').html('Contacting Merchant...');
		Stripe.createToken({
							number: $('.card-number').val(),
							name: $('.card-name').val(),
							cvc: $('.card-cvc').val(),
							exp_month: $('.card-expiry-month').val(),
							address_line1 : $('.card-address1').val(),
							address_line2 : $('.card-address2').val(),
							address_state : $('.card-state').val(),
							address_zip: $('.card-zip').val(),
							exp_year: $('.card-expiry-year').val()
							}, stripeResponseHandler);
		return false; // submit from callback
	});
	";
	return $data;
	
	
	}
	
}