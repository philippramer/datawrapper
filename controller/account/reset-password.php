<?php

//GET route
$app->get('/account/reset-password/:token', function ($token) use ($app) {
    disable_cache($app);

    $page = array();
    add_header_vars($page, 'account');
    if (!empty($token)) {
        $users = UserQuery::create()
          ->filterByResetPasswordToken($token)
          ->find();

        if (count($users) != 1) {
            $page['alert'] = array(
                'type' => 'error',
                'message' => 'This activation token is invalid.'
            );

            error_invalid_password_reset_token();

        } else {
            $user = $users[0];
            // $user->setResetPasswordToken('');
            // $user->save();
            $page['token'] = $token;

            $app->render('account/reset-password.twig', $page);
        }
    }
});


$app->get('/account/profile', function() use ($app) {
    disable_cache($app);

    $user = DatawrapperSession::getUser();

    if ($user->getRole() == 'guest') {
        error_settings_need_login();
        return;
    }

    if ($app->request()->get('token')) {
        // look for action with this token
        $t = ActionQuery::create()
            ->filterByUser($user)
            ->filterByKey('email-change-request')
            ->orderByActionTime('desc')
            ->findOne();
        if (!empty($t)) {
            // check if token is valid
            $params = json_decode($t->getDetails(), true);
            if (!empty($params['token']) && $params['token'] == $app->request()->get('token')) {
                // token matches
                $user->setEmail($params['new-email']);
                $user->save();
                // clear token to prevent future changes
                $params['token'] = '';
                $t->setDetails(json_encode($params));
                $t->save();
            }
        }
    }

    $app->redirect('/account');
});
