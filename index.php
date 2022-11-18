<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Selective\BasePath\BasePathMiddleware;
use Twig\Loader\FilesystemLoader;
use Twig\Environment;
use \Twig\Extension\DebugExtension;

// маршрутизатор
require __DIR__ . '/vendor/autoload.php';
$app = AppFactory::create();
$app->addRoutingMiddleware();
$app->add(new BasePathMiddleware($app));
$app->addErrorMiddleware(true, true, true);

// база данных
include_once 'api/config/database.php';
$db = (new Database())->getConnection();

// шаблонизатор
$loader = new \Twig\Loader\FilesystemLoader('templates');
$view = new Environment($loader, [
    'debug' => true
]);
$view->addExtension(new \Twig\Extension\DebugExtension());

//------------------------------------------------------------------------------------------

function get_data($sql, $db) {

    $rs = $db->Execute($sql);

    $index = 0;
    while (!$rs->EOF){
        for ($n = 0; $n < $rs->Fields->Count; $n++) {
            if(is_null($rs->Fields[$n]->Value)) continue;
            $result[$index][$rs->Fields[$n]->Name] = $rs->Fields[$n]->Value;
        }
        $index = $index + 1;
        $rs->MoveNext();
    }
    return $result;
}

function dd($mixed){
    echo '<pre>'.print_r($mixed,1).'</pre>';
    exit;
}

//------------------------------------------------------------------------------------------

$app->get('/', function (Request $request, Response $response, $args) use($view, $db) {
    
    $template_name = 'index.twig';
    $params = [];

    $tvs_list = get_data("SELECT [id телепередачи], Дата FROM Телепередача", $db);
    $params['tvs_date'] = $tvs_list;

    foreach($tvs_list as &$tv){
        $tv['Игроки'] = get_data("SELECT Игрок.Счет, [Список игроков].Фамилия, [Список игроков].Имя, [Список игроков].Отчество, 0 AS Победитель
                                        FROM [Список игроков] 
                                        LEFT JOIN Игрок ON [Список игроков].[id игрока] = Игрок.[ФИО игрока]
                                        WHERE Игрок.Телепередача = " . $tv['id телепередачи'] . "
                                        ORDER BY Игрок.[Номер игрока]", $db);
        if(!empty($tv['Игроки'])){
            $max_score = get_data("SELECT Max(Игрок.Счет) AS [Max счет]
                                        FROM Игрок WHERE Игрок.Телепередача = " . $tv['id телепередачи'], $db);
            foreach ($tv['Игроки'] as &$player){
                if($player ['Счет'] == $max_score[0]['Max счет']){
                    $player['Победитель'] = 1;
                }
            }
        }
    }
    $params['tvs_list'] = $tvs_list;

    $body = $view->render($template_name, $params);
    $response->getBody()->write($body);
    return $response;
});

$app->get('/tvs/{tv_id}', function (Request $request, Response $response, $args) use($view, $db) {

    $template_name = 'tv-info.twig';
    $params = [];

    $tvs_list = get_data("SELECT [id телепередачи], Дата FROM Телепередача", $db);
    $params['tvs_date'] = $tvs_list;

    $tv = get_data("SELECT * FROM Телепередача WHERE [id телепередачи] = ".$args['tv_id'], $db);
    $params['tv'] = $tv[0];

    $players = get_data("SELECT Игрок.[id игрока в игре], [Список игроков].*, [Список игроков].Фамилия+' '+[Список игроков].Имя+' '+[Список игроков].Отчество AS ФИО
                                        FROM [Список игроков] 
                                        LEFT JOIN Игрок ON [Список игроков].[id игрока] = Игрок.[ФИО игрока]
                                        WHERE Игрок.Телепередача = ".$args['tv_id'] . "
                                        ORDER BY Игрок.[Номер игрока]", $db);

    for ($n = 1; $n <= 5; $n++){
        $round_results = get_data("SELECT [Ответ на вопрос].*, Раунд.[Номер раунда]
                                        FROM (Раунд
                                        INNER JOIN [Вопрос в раунде] ON [Вопрос в раунде].[id раунда] = Раунд.[id раунда])
                                        INNER JOIN [Ответ на вопрос] ON [Ответ на вопрос].[Вопрос] = [Вопрос в раунде].[id вопроса в раунде]      
                                        WHERE Раунд.Телепередача = ".$args['tv_id'] . " AND Раунд.[Номер раунда] = ".$n, $db);


        if(!empty($round_results)){
            foreach ($players as &$player){
                $player['Счет за раунд '.$n] = 0;
                $player['Правильные ответы за раунд '.$n] = 0;
                $player['Неправильные ответы за раунд '.$n] = 0;
                foreach ($round_results as $player_result){
                    if($player['id игрока в игре'] == $player_result['Игрок']){
                        $player['Счет за раунд '.$n] += $player_result['Начисленные очки'];
                        if($player_result['Начисленные очки'] > 0 && $player_result['Номер раунда'] !=4){
                            $player['Правильные ответы за раунд '.$n] += 1;
                        }elseif($player_result['Начисленные очки'] <= 0 && $player_result['Номер раунда'] !=4){
                            $player['Неправильные ответы за раунд '.$n] +=1;
                        }
                    }
                }

                $player['Счет за игру'] += $player['Счет за раунд '.$n];
                $player['Правильные ответы за игру'] += $player['Правильные ответы за раунд '.$n];
                $player['Неправильные ответы за игру'] += $player['Неправильные ответы за раунд '.$n];
            }
        }
    }

    $params['players'] = $players;

    $body = $view->render($template_name, $params);
    $response->getBody()->write($body);
    return $response;
});

$app->run();

?>

<!--SELECT Count([Результаты игрока].[Счет игрока]) AS [Количество побед]-->
<!--    FROM -->
<!--        (-->
<!--            SELECT First([Телепередачи игрока].[Счет игрока]) AS [Счет игрока], Max(Игрок.Счет) AS [Max-Счет] -->
<!--                FROM -->
<!--                    (-->
<!--                        SELECT [Список игроков].[id игрока] AS [id игрока], Телепередача.[id телепередачи] AS [id телепередач игрока], Игрок.[id игрока в игре] AS [id игрока в игре], Игрок.Счет AS [Счет игрока] -->
<!--                        FROM Телепередача -->
<!--                        INNER JOIN ([Список игроков] -->
<!--                        INNER JOIN Игрок ON [Список игроков].[id игрока] = Игрок.[ФИО игрока]) ON Телепередача.[id телепередачи] = Игрок.Телепередача -->
<!--                        WHERE [Список игроков].[id игрока]=[Forms]![Главная форма]![Данные игроков]![id игрока]-->
<!--                    )  AS [Телепередачи игрока]-->
<!--                LEFT JOIN Игрок ON [Телепередачи игрока].[id телепередач игрока] = Игрок.Телепередача GROUP BY Игрок.Телепередача-->
<!--    )  AS [Результаты игрока]-->
<!--WHERE ((([Результаты игрока].[Счет игрока])=[Результаты игрока].[Max-Счет]));-->

<!--$tv['Игроки'] = get_data("SELECT [Список игроков].Фамилия, [Список игроков].Имя, [Список игроков].Отчество, Max(Игрок.Счет) AS [MaxСчет]-->
<!--FROM [Список игроков]-->
<!--LEFT JOIN Игрок ON [Список игроков].[id игрока] = Игрок.[ФИО игрока]-->
<!--WHERE Телепередача = " . $tv['id телепередачи']. "-->
<!--GROUP BY [Список игроков].Фамилия, [Список игроков].Имя, [Список игроков].Отчество", $db);-->

