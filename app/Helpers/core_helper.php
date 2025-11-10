<?php

if (! function_exists('is_admin')) {

    /**
     * checks if logged user is Super Admin.
     *
     * @return bool
     */
    function is_admin()
    {
        return auth()->check() && auth()->user()->isAdmin();
    }
}

if (! function_exists('is_development')) {
    /**
     * Checks if user is physical person.
     */
    function is_development()
    {
        return app()->environment('local');
    }
}

if (! function_exists('calc_percentage')) {

    function calc_percentage($value, $total = 0)
    {
        return $total == 0 ?: round(($value / $total) * 100, 2);
    }
}

if (! function_exists('floattostr')) {
    function floattostr($val)
    {
        preg_match("#^([\+\-]|)([0-9]*)(\.([0-9]*?)|)(0*)$#", trim($val), $o);

        return $o[1].sprintf('%d', $o[2]).($o[3] != '.' ? $o[3] : '');
    }
}

if (! function_exists('check_user_access')) {

    /**
     * Redireciona para a página de Not Found caso o usuário logado não seja
     * Super Admin e esteja tentanto acessar usuários com perfil Super Admin.
     *
     * @return mixed
     */
    function check_user_access($user)
    {
        return abort_if(! is_super_admin() && $user->isSuperAdmin(), 404);
    }
}
