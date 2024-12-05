<div style="display: flex; justify-content: space-between; align-items: center; width: 100%; padding: 10px; background-color: #2a2a2a; color: white; border-bottom: 2px solid #444;">
    <div style="display: flex; align-items: center; gap: 15px;">
        <a href="https://gessointegral.com.br">
            <img
                alt="Gesso Integral"
                src="https://sistema2.gessointegral.com.br/industria/sistema/imagens/gessointegral_new_logo.jpeg"
                width="120"
                style="border-radius: 5px;">
        </a>
        <h1 style="margin: 0;padding-top:1px; font-size: 2.8rem; color: white;">Navigator</h1>
    </div>
    <div>
        <a href="https://www.php.net">
            <img
                alt="PHP"
                src="https://www.php.net/images/logos/new-php-logo.svg"
                width="120"
                style="border-radius: 50%;">
        </a>
    </div>
</div>

<h2 style="color: white; margin-top: 20px; text-align: start;">
    O que é Navigator?
</h2>

<p>
    O <strong>Navigator</strong> é um pacote PHP inspirado nos sistemas de roteamento do <strong>Express.js</strong> (Node.js) e do <strong>Navigator</strong> (PHP). Ele visa ser um esqueleto de projeto PHP simples de configurar e usar, permitindo inicializações rápidas e flexíveis.
</p>

---

# Requisitos Iniciais

O <strong>Navigator</strong> requer algumas configurações básicas para funcionar corretamente, como a ativação da extensão <code>pdo_mysql</code>. Abaixo, explicamos como verificar e ativar esta extensão em seu ambiente PHP.

<p>
  Procure por <code>extension=pdo_mysql</code> e <code>extension=fileinfo</code> no arquivo <code>php.ini</code>.
  Caso esteja comentado (com um ponto e vírgula <code>;</code> no início),
  remova o <code>;</code> para ativar a extensão:
</p>

<pre><code class="ini">
;extension=pdo_mysql
;extension=fileinfo
</code></pre>

<p>Altere para:</p>

<pre><code class="ini">
extension=pdo_mysql
extension=fileinfo
</code></pre>

### Configuração Inicial

criando uma <code>instacia</code> para acessar os metodos

```php
// Caso o seu autoload seja via Composer
require 'vendor/autoload.php';
// Classe responsável pelo load da rota
require 'app/core/configrouter/Navigator.php';

$router = Navigator::router();

$router->get('/', function () {
  echo 'Hello, World!';
});

Navigator::start();
```

 <h1>Ou</h1>usando o proprio <code>objeto</code> pra acessar os metodos

```php
// Caso o seu autoload seja via Composer
require 'vendor/autoload.php';
// Classe responsável pelo load da rota
require 'app/core/configrouter/Navigator.php';

Navigator::get('/', function () {
  echo 'Hello, World!';
});

Navigator::start();
```

# atençao no final do index ou do arquivo de rota

use <code>Navigator::start();</code> pra avisar o ponto final.

```php
require 'vendor/autoload.php';

... codigo

Navigator::start();
```

---

# Métodos Disponíveis no Navigator

O Navigator fornece diversos métodos para gerenciar rotas de forma simples e eficiente. Aqui estão os métodos disponíveis e exemplos de uso:

---

### Sintaxe:

Registra uma rota para requisições HTTP do tipo `GET`.

```php
Navigator::get('/', function () {
  echo 'Hello, World!';
});
```

---

Registra uma rota para requisições HTTP do tipo `POST`.

```php
Navigator::post('/', function () {
  echo 'Hello, World!';
});
```

---

Registra uma rota para requisições HTTP do tipo `PUT`.

```php
Navigator::put('/', function () {
    echo 'Recurso atualizado!';
});
```

---

Registra uma rota para requisições HTTP do tipo `PATCH`.

```php
Navigator::patch('/', function () {
    echo 'Recurso parcialmente atualizado!';
});
```

---

Registra uma rota para requisições HTTP do tipo `DELETE`.

```php
Navigator::delete('/', function () {
    echo 'Recurso excluído!';
});
```

---

## Rotas adicionais:

Registra uma rota para requisições **ACEITA TODOS OS METODOS** para `GROUP`.

Cria um grupo de rotas com um prefixo compartilhado.

a saida seria
<code> http://localhost/admin/dashboard </code> e
<code> http://localhost/localhost/admin/login </code>

```php
// /admin/dashboard
Navigator::group('/admin', function () {
    Navigator::get('/dashboard', function () {
        echo 'Painel de administração';
    });
// /admin/login
    Navigator::post('/login', function () {
        echo 'Login realizado';
    });
});
```

---

Registra uma rota que aceita múltiplos métodos HTTP: GET, POST, PUT, PATCH, DELETE para `MAP`.

A função MAP é usada para registrar rotas que aceitam múltiplos métodos HTTP. Ela permite uma maior flexibilidade ao definir os métodos que a rota aceita, e suporta o uso de caracteres curingas para capturar partes dinâmicas da URL.

#### aceita todos os metodos

```php
// aceita todos os metodos
Navigator::map('*', function () {
 echo 'usando caracteres curingas';
});
```

#### Ou definindo os metodos aceitos

```php
//vai aceita apenas GET
Navigator::map('GET *', function () {
  echo 'usando caracteres curingas';
});
```

#### Ou pode adiciona mais de um metodo

```php
//vai aceita apenas POST E GET
Navigator::map('GET|POST *', function () {
   echo 'usando caracteres curingas';
});
```

---

# Parametros

a entrada seria http://localhost/user/victor

```php
Navigator::get('/user/@name', function ($name) {
  echo "olá $name";
});
```

a entrada seria http://localhost/user?name=victor

```php
Navigator::get('/user', function () {

   $name = Navigator::request()->query['name'];

   echo "olá $name";
});
```

a entrada seria http://localhost/user

```json
{
  "name": "victor"
}
```

```php

Navigator::post('/user', function () {

    $name = Navigator::request()->data['name'];

    echo "Olá $name";
});
```

Então, para obter um parâmetro de string de consulta, você pode fazer:

```php
$id = Navigator::request()->query['id'];
```

Ou você pode fazer:

```php
$id = Navigator::request()->query->id;
```

Corpo da solicitação RAW
Para obter o corpo bruto da solicitação HTTP, por exemplo, ao lidar com solicitações PUT, você pode fazer:

```php
$body = Navigator::request()->getBody();
```

Entrada JSON

```json
Se você enviar uma solicitação com o tipo application/jsone os dados, {"id": 123} eles estarão disponíveis na datapropriedade:
```

Você pode acessar o $\_COOKIEarray através da propriedade cookies:

```php
$myCookieValue = Navigator::request()->cookies['myCookieName'];
```

Há um atalho disponível para acessar o $\_SERVER array através do método getVar():

```php
$host = Navigator::request()->getVar['HTTP_HOST'];
```

Acessando arquivos enviados via $\_FILES
Você pode acessar os arquivos enviados por meio da filespropriedade:

```php
$uploadedFile = Navigator::request()->files['myFile'];
```

Você pode processar uploads de arquivos usando o Navigator com alguns métodos auxiliares. Basicamente, resume-se a extrair os dados do arquivo da solicitação e movê-los para um novo local.

```php
Navigator::route('POST /upload', function(){
// If you had an input field like <input type="file" name="myFile">
$uploadedFileData = Navigator::request()->getUploadedFiles();
$uploadedFile = $uploadedFileData['myFile'];
$uploadedFile->moveTo('/path/to/uploads/' . $uploadedFile->getClientFilename());
});
```

Se você tiver vários arquivos enviados, você pode percorrê-los:

```php
Navigator::route('POST /upload', function(){
// If you had an input field like <input type="file" name="myFiles[]">
$uploadedFiles = Navigator::request()->getUploadedFiles()['myFiles'];
    foreach ($uploadedFiles as $uploadedFile) {
$uploadedFile->moveTo('/path/to/uploads/' . $uploadedFile->getClientFilename());
}
});
```

Nota de segurança: sempre valide e higienize a entrada do usuário, especialmente ao lidar com uploads de arquivos. Sempre valide o tipo de extensões que você permitirá que sejam carregadas, mas você também deve validar os "bytes mágicos" do arquivo para garantir que ele seja realmente o tipo de arquivo que o usuário afirma ser.

Cabeçalhos de solicitação
Você pode acessar os cabeçalhos de solicitação usando o método getHeader()ou :getHeaders()

```php
// alvez você precise do cabeçalho de autorização
$host = Navigator::request()->getHeader('Authorization');

// ou
$host = Navigator::request()->header('Authorization');
```

```php
// Se você precisar pegar todos os cabeçalhos
$headers = Navigator::request()->getHeaders();
// or
$headers = Navigator::request()->headers();
```

Método de solicitação
Você pode acessar o método de solicitação usando a methodpropriedade ou o getMethod()método:

```php
$method = Navigator::request()->method; // na verdade chama getMethod()
$method = Navigator::request()->getMethod();
```

Observação: o getMethod()método primeiro extrai o método de $\_SERVER, então ele pode ser substituído por $\_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] se existir ou $\_REQUEST['_method'] se existir.
URLs de solicitação
Existem alguns métodos auxiliares para juntar partes de uma URL para sua conveniência.

URL completa
Você pode acessar o URL completo da solicitação usando o getFullUrl()método:

```php
$url = Navigator::request()->getFullUrl();
// https://example.com/some/path?foo=bar
```

URL base
Você pode acessar a URL base usando o getBaseUrl()método:

```php
$url = Navigator::request()->getBaseUrl();
// Notice, no trailing slash.
// https://example.com
```

Análise de consulta
Você pode passar uma URL para o parseQuery()método para analisar a string de consulta em uma matriz associativa:

```php
$query = Navigator::request()->parseQuery('https://example.com/some/path?foo=bar');
// ['foo' => 'bar']
```

Respostas
O Navigator ajuda a gerar parte dos cabeçalhos de resposta para você, mas você detém a maior parte do controle sobre o que envia de volta ao usuário. Às vezes, você pode acessar o Responseobjeto diretamente, mas na maioria das vezes você usará a Navigatorinstância para enviar uma resposta.

Enviando uma resposta básica
O Navigator usa ob_start() para armazenar em buffer a saída. Isso significa que você pode usar echoou printpara enviar uma resposta ao usuário e o Navigator irá capturá-la e enviá-la de volta ao usuário com os cabeçalhos apropriados.

// HTTP/1.1 200 OK
// Content-Type: text/html
//
// Hello, World!
Como alternativa, você write()também pode chamar o método para adicionar ao corpo.

```php
// Isso enviará "Hello, World!" para o navegador do usuário
Navigator::route('/', function() {
// detalhado, mas às vezes consegue o trabalho quando você precisa
Navigator::response()->write("Hello, World!");

    // if you want to retrieve the body that you've set at this point
    // you can do so like this
    $body = Navigator::response()->getBody();

});
```

Códigos de status
Você pode definir o código de status da resposta usando o statusmétodo:

```php
Navigator::route('/@id', function($id) {
    if($id == 123) {
Navigator::response()->status(200);
echo "Hello, World!";
} else {
Navigator::response()->status(403);
echo "Forbidden";
}
});
```

Se você quiser obter o código de status atual, você pode usar o statusmétodo sem nenhum argumento:

```php
Navigator::response()->status(); // 200
```

Definindo um corpo de resposta
Você pode definir o corpo da resposta usando o writemétodo , no entanto, se você ecoar ou imprimir qualquer coisa, ela será capturada e enviada como o corpo da resposta via buffer de saída.

```php
Navigator::route('/', function() {
Navigator::response()->write("Hello, World!");
});

// igual a

Navigator::route('/', function() {
echo "Hello, World!";
});
```

Limpando um corpo de resposta
Se você quiser limpar o corpo da resposta, você pode usar o clearBodymétodo:

```php
Navigator::route('/', function() {
if($someCondition) {
Navigator::response()->write("Hello, World!");
} else {
Navigator::response()->clearBody();
}
});
```

Executando um retorno de chamada no corpo da resposta
Você pode executar um retorno de chamada no corpo da resposta usando o addResponseBodyCallbackmétodo:

```php
Navigator::route('/users', function() {
$db = Navigator::db();
$users = $db->fetchAll("SELECT \* FROM users");
Navigator::render('users_table', ['users' => $users]);
});

// Isso compactará todas as respostas para qualquer rota
Navigator::response()->addResponseBodyCallback(function($body) {
    return gzencode($body, 9);
});
```

Você pode adicionar vários callbacks e eles serão executados na ordem em que foram adicionados. Como isso pode aceitar qualquer callback , ele pode aceitar um class array [ \$class, 'method' ], um encerramento $strReplace = function(\$body) { str_replace('hi', 'there', \$body); };
ou um nome de função 'minify'se você tivesse uma função para minificar seu código html, por exemplo.

Observação: os retornos de chamada de rota não funcionarão se você estiver usando a Navigator.v2.output_bufferingopção de configuração.

Retorno de chamada de rota específica
Se você quiser que isso se aplique apenas a uma rota específica, você pode adicionar o retorno de chamada na própria rota:

```php
Navigator::route('/users', function() {
$db = Navigator::db();
$users = $db->fetchAll("SELECT \* FROM users");
Navigator::render('users_table', ['users' => $users]);

    // This will gzip only the response for this route
    Navigator::response()->addResponseBodyCallback(function($body) {
        return gzencode($body, 9);
    });

});
```

Opção de Middleware
Você também pode usar middleware para aplicar o retorno de chamada a todas as rotas via middleware:

```php
// MinifyMiddleware.php
class MinifyMiddleware {
public function before() {
// Aplique o retorno de chamada aqui no objeto response().
Navigator::response()->addResponseBodyCallback(function($body) {
            return $this->minify($body);
});
}

    protected function minify(string $body): string {
        // otimiza o corpo de alguma forma
        return $body;
    }

}
```

atençao os Middleware pode ser aplicados <code> apenas em rotas do tipo group</code>
mais caso querira usalos em outros metodos use dentro do calback da funçao

```php

// index.php
Navigator::group('/users', function() {
Navigator::route('', function() { /_ ... _/ });
Navigator::route('/@id', function($id) { /_ ... _/ });
}, [ new MinifyMiddleware() ]);
```

Definindo um cabeçalho de resposta
Você pode definir um cabeçalho, como o tipo de conteúdo da resposta, usando o headermétodo:

```php
// This will send "Hello, World!" to the user's browser in plain text
Navigator::route('/', function() {
Navigator::response()->header('Content-Type', 'text/plain');
// or
Navigator::response()->setHeader('Content-Type', 'text/plain');
echo "Hello, World!";
});

```

JSON
O Navigator fornece suporte para enviar respostas JSON e JSONP. Para enviar uma resposta JSON, você passa alguns dados para serem codificados em JSON:

```php
Navigator::json(['id' => 123]);
```

JSON com código de status
Você também pode passar um código de status como segundo argumento:

```php
Navigator::json(['id' => 123], 201);
```

JSON com impressão bonita
Você também pode passar um argumento para a última posição para habilitar uma impressão bonita:

```php
Navigator::json(['id' => 123], 200, true, 'utf-8', JSON_PRETTY_PRINT);
```

Se você estiver alterando opções passadas Navigator::json()e quiser uma sintaxe mais simples, você pode simplesmente remapear o método JSON:

```php
Navigator::map('json', function($data, $code = 200, $options = 0) {
    Navigator::_json($data, $code, true, 'utf-8', $options);
}
```

```php
// e agora pode ser usado assim
Navigator::json(['id' => 123], 200, JSON_PRETTY_PRINT);
```

Se você quiser enviar uma resposta JSON e parar a execução, você pode usar o jsonHaltmétodo . Isso é útil para casos em que você está verificando talvez algum tipo de autorização e se o usuário não estiver autorizado, você pode enviar uma resposta JSON imediatamente, limpar o conteúdo do corpo existente e parar a execução.

```php
Navigator::route('/users', function() {
$authorized = someAuthorizationCheck();
    // Check if the user is authorized
    if($authorized === false) {
Navigator::jsonHalt(['error' => 'Unauthorized'], 401);
}
});
```

JSONP
Para solicitações JSONP, você pode, opcionalmente, passar o nome do parâmetro de consulta que está usando para definir sua função de retorno de chamada:

```php
Navigator::jsonp(['id' => 123], 'q');
Então, ao fazer uma requisição GET usando ?q=my_func, você deve receber a saída:
```

```php
my_func({"id":123});
```

Se você não passar um nome de parâmetro de consulta, o padrão será jsonp.

Redirecionar para outra URL
Você pode redirecionar a solicitação atual usando o redirect()método e passando uma nova URL:

```php
Navigator::redirect('/new/location');
```

Por padrão, o Navigator envia um código de status HTTP 303 ("Ver outro"). Você pode opcionalmente definir um código personalizado:

```php
Navigator::redirect('/new/location', 401);
```

Parando
Você pode parar o framework a qualquer momento chamando o haltmétodo:

```php
Navigator::halt();
```

Você também pode especificar um HTTPcódigo de status e uma mensagem opcionais:

```php
Navigator::halt(200, 'Be right back...');
```

A chamada haltdescartará qualquer conteúdo de resposta até esse ponto. Se você quiser parar o framework e gerar a resposta atual, use o stopmétodo:

```php
Navigator::stop();
```

Limpando dados de resposta
Você pode limpar o corpo da resposta e os cabeçalhos usando o clear()método . Isso limpará todos os cabeçalhos atribuídos à resposta, limpará o corpo da resposta e definirá o código de status como 200.

```php
Navigator::response()->clear();
```

Limpando apenas o corpo da resposta
Se você quiser apenas limpar o corpo da resposta, você pode usar o clearBody()método:

```php
//Isso ainda manterá todos os cabeçalhos definidos no objeto response().
Navigator::response()->clearBody();
```

Cache HTTP
O Navigator fornece suporte integrado para cache de nível HTTP. Se a condição de cache for atendida, o Navigator retornará uma 304 Not Modifiedresposta HTTP. Na próxima vez que o cliente solicitar o mesmo recurso, ele será solicitado a usar sua versão armazenada em cache localmente.

Cache de nível de rota
Se quiser armazenar em cache toda a sua resposta, você pode usar o cache()método e passar o tempo para o cache.

```php
// Isso armazenará a resposta em cache por 5 minutos
Navigator::route('/news', function () {
Navigator::response()->cache(time() + 300);
echo 'This content will be cached.';
});
```

```php
// Alternativamente, você pode usar uma string que você passaria
// para o método strtotime()
Navigator::route('/news', function () {
Navigator::response()->cache('+5 minutes');
echo 'Este conteúdo será armazenado em cache.';
});
```

Última modificação
Você pode usar o lastModifiedmétodo e passar um timestamp UNIX para definir a data e a hora em que uma página foi modificada pela última vez. O cliente continuará a usar seu cache até que o último valor modificado seja alterado.

```php
Navigator::route('/news', function () {
Navigator::lastModified(1234567890);
echo 'This content will be cached.';
});
```

ETag
ETagO cache é semelhante ao Last-Modified, exceto que você pode especificar qualquer id que desejar para o recurso:

```php
Navigator::route('/news', function () {
Navigator::etag('my-unique-id');
echo 'This content will be cached.';
});
```

Tenha em mente que chamar lastModifiedou etagdefinirá e verificará o valor do cache. Se o valor do cache for o mesmo entre as solicitações, o Navigator enviará imediatamente uma HTTP 304resposta e interromperá o processamento.

Há um método auxiliar para baixar um arquivo. Você pode usar o downloadmétodo e passar o caminho.

```php
Navigator::route('/download', function () {
Navigator::download('/path/to/file.txt');
});
```

# Mensagem de Agradecimento

<div style="text-align: center; margin-top: 20px;">
    <img loop=infinite src="https://i.pinimg.com/originals/b0/33/9e/b0339e369ffe544ed24a52cbad426ccd.gif" alt="Motivational GIF" width="300">
</div>

Agradecemos sinceramente por dedicar seu tempo a ler esta documentação. Esperamos que você encontre tudo o que precisa de forma clara e direta. Trabalhamos com empenho para tornar o Navigator uma ferramenta simples e eficiente, permitindo a criação de aplicações e APIs com facilidade. Se chegou até aqui, esperamos que este projeto seja útil para você. Caso tenha dúvidas ou sugestões, sinta-se à vontade para entrar em contato.

Desejamos a você uma ótima experiência ao utilizar o Navigator. Muito sucesso em seus projetos!

tchauuuuuuu.
