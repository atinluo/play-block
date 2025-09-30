(function(){
	function createUI(){
		var btn = document.createElement('button');
		btn.id = 'wpcr-fab';
		btn.type = 'button';
		btn.innerText = 'Chat';

		var panel = document.createElement('div');
		panel.id = 'wpcr-panel';
		panel.innerHTML = ''+
			'<div class="wpcr-header">'+ (WPCR && WPCR.site ? WPCR.site : 'Assistant') +'</div>'+
			'<div class="wpcr-messages" id="wpcr-messages"></div>'+
			'<form class="wpcr-input" id="wpcr-form">'+
				'<input type="text" id="wpcr-text" placeholder="Ask me anything..." autocomplete="off" />'+
				'<button type="submit">Send</button>'+
			'</form>';

		document.body.appendChild(btn);
		document.body.appendChild(panel);

		btn.addEventListener('click', function(){
			panel.classList.toggle('open');
		});

		document.getElementById('wpcr-form').addEventListener('submit', async function(e){
			e.preventDefault();
			var input = document.getElementById('wpcr-text');
			var text = input.value.trim();
			if(!text){return;}
			appendMessage('user', text);
			input.value = '';
			appendMessage('bot', '...');
			try{
				var res = await fetch(WPCR.root + '/chat', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-WP-Nonce': WPCR.nonce
					},
					body: JSON.stringify({ message: text })
				});
				var data = await res.json();
				replaceLastBot(data.reply || '');
			} catch(err){
				replaceLastBot('Sorry, something went wrong.');
			}
		});
	}

	function appendMessage(role, text){
		var list = document.getElementById('wpcr-messages');
		var item = document.createElement('div');
		item.className = 'wpcr-msg ' + role;
		item.textContent = text;
		list.appendChild(item);
		list.scrollTop = list.scrollHeight;
	}

	function replaceLastBot(text){
		var list = document.getElementById('wpcr-messages');
		for(var i=list.children.length-1;i>=0;i--){
			if(list.children[i].classList.contains('bot')){
				list.children[i].textContent = text;
				break;
			}
		}
		list.scrollTop = list.scrollHeight;
	}

	document.addEventListener('DOMContentLoaded', createUI);
})();
