const form = document.querySelector('.message-form');
const input = form.querySelector('#message');

const socket = new WebSocket('ws://localhost:8000/server.php');

form.addEventListener('submit', event => {
    event.preventDefault();
    const message = input.value;
    input.value = '';
});

socket.addEventListener('message', message => {
    console.log(message.data);
});
