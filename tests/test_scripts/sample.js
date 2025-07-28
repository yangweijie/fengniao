// 示例JavaScript脚本
document.querySelector("#username").value = "testuser";
document.querySelector("#password").value = "testpass";
document.querySelector("#login-button").click();

setTimeout(function() {
    document.querySelector("#dashboard-link").click();
}, 2000);

waitForSelector("#welcome-message");
