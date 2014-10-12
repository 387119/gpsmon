<link href="/css/login.css" rel="stylesheet" />
<?
echo $errshow;
?>
<form id="loginForm" action="" method="post">
<input type=hidden name=typelogin value=newlogin>
	<div class="field">
		<label>Имя пользователя:</label>
		<div class="input"><input type="text" name="login" value="" id="login" /></div>
	</div>

	<div class="field">
<!--		<a href="#" id="forgot">Забыли пароль?</a>-->
		<label>Пароль:</label>
		<div class="input"><input type="password" name="passwd" value="" id="pass" /></div>
	</div>

	<div class="submit">
		<button type="submit">Войти</button>
<!--		<label id="remember"><input name="" type="checkbox" value="" /> Запомнить меня</label>-->
	</div>
<div>
Демо вход: demo/demo
</div>
</form>

</body>
