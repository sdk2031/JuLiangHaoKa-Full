<?php
namespace app\api\middleware;
/**
 * 
 * User-Agent的服务🆕
 */
class EnsureUserAgent
{
    public function handle($request, \Closure $next)
    {
        if (!isset($_SERVER['HTTP_USER_AGENT'])) {
            $_SERVER['HTTP_USER_AGENT'] = 'ServerCallback/1.0';
        }
        
        return $next($request);
    }
}
