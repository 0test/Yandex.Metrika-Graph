<?php 
$e = &$modx->Event;
switch($e->name){
	case 'OnManagerWelcomeHome':
		$id = $params['app_id'];
		$token = $params['app_token'];
		$counter_id = $params['counter_id'];
		if($params['show_dev_links']){
			$metrika_content .= '<a href="https://oauth.yandex.ru/client/new" target="_blank">Создать приложение</a>';
			$metrika_content .= '<BR>';
			$metrika_content .= '<a href="https://oauth.yandex.ru/authorize?response_type=token&client_id=' . $id . '" target="_blank">Получить доступ к счётчику</a>';
			$metrika_content .= '<BR>';
		}

		$context = stream_context_create(array(
			'http' => array(
				'method' => 'GET',
				'header' => 'Authorization: OAuth ' . $token . PHP_EOL.
				'Content-Type: application/x-yametrika+json' . PHP_EOL
			),
		));

		$url = 'https://api-metrika.yandex.net/stat/v1/data';
		$y_params = [
			'ids'         => $counter_id,
			'oauth_token' => $token,
			'metrics'     => 'ym:s:visits,ym:s:pageviews,ym:s:users',
			'dimensions'  => 'ym:s:date',
			'date1'       => '7daysAgo',
			'date2'       => 'yesterday',
			'sort'        => 'ym:s:date',
		];
		$json = json_decode(file_get_contents( $url . '?' . http_build_query($y_params), false, $context), true);
		$data = $json['data'];

		$tmpdata = [];
		foreach($data as $item) {
			$tmpdata['visits'][]     = $item['metrics'][0];
			$tmpdata['pageviews'][]  = $item['metrics'][1];
			$tmpdata['users'][]      = $item['metrics'][2];
			$tmpdata['categories'][] = $item['dimensions'][0]['name'];
		}
		$categories = json_encode($tmpdata['categories'], JSON_UNESCAPED_UNICODE);
		$series = json_encode([
			[ 'name' => 'Визиты',     'data' => $tmpdata['visits'] ],
			[ 'name' => 'Просмотры',  'data' => $tmpdata['pageviews'] ],
			[ 'name' => 'Посетители', 'data' => $tmpdata['users'] ]
		], JSON_UNESCAPED_UNICODE);
		$metrika_content .= '<script src="https://code.highcharts.com/highcharts.js"></script>';       
		$metrika_content .=  '<div id="container"></div>';
		$metrika_content .= "<script>
            Highcharts.chart('container', {
              chart: {
                type: 'spline'
              },
              title: {
                text: 'Активность посетителей за 7 дней',
                x: -20
              },
              xAxis: {
                categories: $categories
              },
              yAxis: {
                title: {
                    text: 'Количество'
                }
              },
              legend: {
                layout: 'vertical',
                align: 'right',
                verticalAlign: 'middle',
                borderWidth: 0
              },
              series: $series
            });
            </script>";    

		$widgets['yandex-metrika'] = array(
			'menuindex' => $params['menuindex'],
			'id' => 'yandex-metrika',
			'cols' => 'col-sm-' . $params['widget_width'],
			'icon' => 'fa-bar-chart',
			'title' => 'Метрика',
			'body' => '<div class="card-body">'.$metrika_content.'</div>'
		);
		$modx->event->output(serialize($widgets));
		break;
}