<?php


declare(strict_types=1);

use Lcobucci\Clock\SystemClock;
use Lexik\Bundle\JWTAuthenticationBundle\Services\WebToken\AccessTokenLoader;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\abstract_arg;

return function (ContainerConfigurator $container): void {
    $container = $container->services()
        ->defaults()
        ->private()
        ->autoconfigure()
        ->autowire();

    $container->set('lexik_jwt_authentication.web_token.clock')
        ->class(SystemClock::class)
        ->factory([SystemClock::class, 'fromUTC'])
    ;

    $container->set('lexik_jwt_authentication.access_token_loader')
        ->class(AccessTokenLoader::class)
        ->args([
            service(\Jose\Bundle\JoseFramework\Services\JWSLoaderFactory::class),
            service(\Jose\Bundle\JoseFramework\Services\JWELoaderFactory::class),
            service(\Jose\Bundle\JoseFramework\Services\ClaimCheckerManagerFactory::class),
            abstract_arg('Claim checkers'),
            abstract_arg('JWS header checkers'),
            abstract_arg('Mandatory claims'),
            abstract_arg('Allowed signature algorithms'),
            abstract_arg('Signature keyset'),
            abstract_arg('Continue on decryption failure'),
            abstract_arg('JWE header checkers'),
            abstract_arg('Allowed key encryption algorithms'),
            abstract_arg('Allowed content encryption algorithms'),
            abstract_arg('Encryption keyset'),
        ])
    ;

    $container->set('lexik_jwt_authentication.web_token.iat_validator')
        ->class(\Jose\Component\Checker\IssuedAtChecker::class)
        ->args([
            '$clock' => service('lexik_jwt_authentication.web_token.clock'),
            '$allowedTimeDrift' => param('lexik_jwt_authentication.clock_skew'),
            '$protectedHeaderOnly' => true,
        ])
        ->tag('jose.checker.claim', ['alias' => 'iat_with_clock_skew'])
        ->tag('jose.checker.header', ['alias' => 'iat_with_clock_skew'])
    ;

    $container->set('lexik_jwt_authentication.web_token.exp_validator')
        ->class(\Jose\Component\Checker\ExpirationTimeChecker::class)
        ->args([
            '$clock' => service('lexik_jwt_authentication.web_token.clock'),
            '$allowedTimeDrift' => param('lexik_jwt_authentication.clock_skew'),
            '$protectedHeaderOnly' => true,
        ])
        ->tag('jose.checker.claim', ['alias' => 'exp_with_clock_skew'])
        ->tag('jose.checker.header', ['alias' => 'exp_with_clock_skew'])
    ;

    $container->set('lexik_jwt_authentication.web_token.nbf_validator')
        ->class(\Jose\Component\Checker\NotBeforeChecker::class)
        ->args([
            '$clock' => service('lexik_jwt_authentication.web_token.clock'),
            '$allowedTimeDrift' => param('lexik_jwt_authentication.clock_skew'),
            '$protectedHeaderOnly' => true,
        ])
        ->tag('jose.checker.claim', ['alias' => 'nbf_with_clock_skew'])
        ->tag('jose.checker.header', ['alias' => 'nbf_with_clock_skew'])
    ;
};
