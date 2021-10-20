$(document).ready(function () {

    sendGetRequest();  // первичная загрузка данных

    if(!localStorage.getItem('key')){
        localStorage.setItem('key', "5aa3c281e42ba7101f7227a7519d5e961c7bcf2b10a42914304bffc1afcebb1d2be98f53caa80d05")
    }
    
    let currentUser = undefined;

    $(".input-area").change(function () {  // оживление ввода логина
        if($(this).val()){
            currentUser = $(this).val();
            $('.user-login').text(currentUser);
        }
        // если поле было очищено, то сохраняется предыдущее занчение
    });

    $(".send-icon").click(function () { // оживление кнопки отправки сообщения
        let text = $(".send-message").val();
        if(!text) return; // если поле текста пустое, сообщение не отправляется
        $(".send-message").val(''); // очищение поля
        if(!currentUser){
            $(".send-message").val('Пользователь не авторизован!');
            return;
        }
        let date = toSqlDatetime(new Date())
        message={
            user: currentUser,
            text,
            date
        };

        sendPostRequest(message); // отправка запроса на создание записи
    });

    $(".close-icon").click(function () { // оживление кнопки удаления статичных сообщений
        $(this).parent().remove();
    });

function mapObjectToHTML(message){ // данные преобразуются в HTML-разметку
    let parsedDate = new Date(message.date);  // время может поступать в качестве строки из ответа базы данных при первичной загрузке

    encryptedUser = CryptoJS.AES.decrypt(message.user, localStorage.getItem('key'));
    encryptedText = CryptoJS.AES.decrypt(message.text, localStorage.getItem('key'));
    user = encryptedUser.toString(CryptoJS.enc.Utf8);
    text = encryptedText.toString(CryptoJS.enc.Utf8);

    let div_message = $(
        `<div class="message" id="${message.id}">
            <div class="user">${user}</div> 
            <div class="message-content">
                ${text}
                <span class="message-time">${parsedDate.getHours()}:${parsedDate.getMinutes()}</span>
            </div>
        </div>`
    ).appendTo($('.chat-window'));

    let input_close = $('<input/>', {
        'class' : 'close-icon',
        'src' : "img/close-icon.svg",
        'type' : "image"
    }).appendTo(div_message);
    input_close.click(function () {  // оживление кнопки удаления динамически добавленных сообщений
        let beingDeleted = $(this).parent().attr("id");
        sendDeleteRequest({id: beingDeleted});
        $(this).parent().remove();
    })
}

function sendGetRequest(){
    $.getJSON("https://php-server-test1.herokuapp.com/api/message/read.php", function(data){
        for(let message of data.records){
            mapObjectToHTML(message);
        };
    });
}

function sendPostRequest(data){
    data.user = CryptoJS.AES.encrypt(data.user, localStorage.getItem('key')).toString();
    data.text = CryptoJS.AES.encrypt(data.text, localStorage.getItem('key')).toString();
    $.post( "https://php-server-test1.herokuapp.com/api/message/create.php", JSON.stringify(data), function (data){
        mapObjectToHTML(data);  // при создании нового сообщения вместо повторной отправки get-запроса 
                                // просто создается новая разметка, в дальнейшем она сохранится при обновлении страницы
    });
}

function sendDeleteRequest(data){
    $.post( "https://php-server-test1.herokuapp.com/api/message/delete.php", JSON.stringify(data));
}

const toSqlDatetime = (inputDate) => { // преобразование в datetime формат в mySQL
    const date = new Date(inputDate)
    const dateWithOffest = new Date(date.getTime() - (date.getTimezoneOffset() * 60000))
    return dateWithOffest
        .toISOString()
        .slice(0, 19)
        .replace('T', ' ')
}

});