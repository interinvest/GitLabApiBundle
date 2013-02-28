<?php

namespace Zeichen32\GitLabApiBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class Zeichen32GitLabApiExtension extends Extension
{
    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        $this->addClients($config['clients'], $container);

        if(isset($config['issue_tracker']) && isset($config['issue_tracker']['project']) && !is_null($config['issue_tracker']['project']))
        {
            $this->setIssueClient($container, $config['issue_tracker']['client']);
            $container->setParameter('zeichen32_gitlabapi.config.project', $config['issue_tracker']['project']);
        }
    }

    private function addClients(array $clients, ContainerBuilder $container) {
        foreach($clients as $name => $client) {
            $this->createClient($name, $client['url'], $client['token'], $container);
        }

        reset($clients);
        $this->setDefaultClient(key($clients), $container);
    }

    private function setDefaultClient($name, ContainerBuilder $container) {
        $container->setAlias('zeichen32_gitlabapi.client.default', sprintf('zeichen32_gitlabapi.client.%s', $name));
    }

    private function setIssueClient(ContainerBuilder $container, $name) {
        $container->setAlias('zeichen32_gitlabapi.client.issue', sprintf('zeichen32_gitlabapi.client.%s', $name));
    }

    private function createClient($name, $url, $token, ContainerBuilder $container) {

        $definition = new Definition('%zeichen32_gitlabapi.client.class%', array(
            $url
        ));

        $definition->addMethodCall('authenticate', array(
            $token,
            'url_token'
        ))
            ->setScope('request')
        ;

        $container->setDefinition(
            sprintf('zeichen32_gitlabapi.client.%s', $name),
            $definition
        );
    }
}
