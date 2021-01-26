const form = document.querySelector('.message-form');
const messages = document.querySelector('.messages');
const input = form.querySelector('#message');
const nick = form.querySelector('#nick');

const socket = new WebSocket('ws://localhost:8000');

form.addEventListener('submit', event => {
    event.preventDefault();
    socket.send(JSON.stringify({ [nick.value]: input.value }));
    input.value = '';
});

socket.addEventListener('message', message => {
    const data = JSON.parse(message.data);
    const p = document.createElement('p');
    const nick = Object.keys(data)[0];
    p.textContent = `<${nick}> ${data[nick]}`;
    messages.append(p);
});
