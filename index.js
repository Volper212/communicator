(async () => {
    const [form, messages, message, nickForm, nickInput] =
        ['message-form', 'messages', 'message', 'nick-form', 'nick'].map(id => document.getElementById(id));

    let nick;

    nickForm.addEventListener('submit', event => {
        event.preventDefault();
        nick = nickInput.value;
        message.focus();
        nickForm.remove();
    });

    const socket = new WebSocket(`ws://${await (await fetch('ip.txt')).text()}:8000`);

    form.addEventListener('submit', event => {
        event.preventDefault();
        if (!message.value) return;
        socket.send(JSON.stringify({ [nick]: message.value }));
        message.value = '';
    });

    socket.addEventListener('error', () => {
        alert('Kamkar wyłączył serwa albo coś się spieprzyło.\nBeka z ciebie.');
    });

    socket.addEventListener('message', message => {
        const data = JSON.parse(message.data);
        const p = document.createElement('p');
        const nick = Object.keys(data)[0];
        p.textContent = `<${nick}> ${data[nick]}`;
        const isScrolled = messages.scrollTop === messages.scrollHeight - messages.clientHeight;
        messages.append(p);
        if (isScrolled) {
            messages.scrollTop = messages.scrollHeight - messages.clientHeight;
        }
    });
})();