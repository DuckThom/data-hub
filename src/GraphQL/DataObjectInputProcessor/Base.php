<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\DataHubBundle\GraphQL\DataObjectInputProcessor;

use GraphQL\Type\Definition\ResolveInfo;
use Pimcore\Bundle\DataHubBundle\GraphQL\Service;
use Pimcore\Bundle\DataHubBundle\GraphQL\Traits\ServiceTrait;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\Concrete;


class Base
{

    use ServiceTrait;

    protected $nodeDef;


    /**
     * Base constructor.
     * @param $nodeDef
     */
    public function __construct($nodeDef)
    {
        $this->nodeDef = $nodeDef;
    }

    /**
     * @return mixed
     */
    public function getAttribute() {
        return $this->nodeDef["attributes"]["attribute"];
    }

    /**
     * @param Concrete $object
     * @param $newValue
     * @param $args
     * @param array $context
     * @param ResolveInfo $info
     * @throws \Exception
     */
    public function process(Concrete $object, $newValue, $args, $context, ResolveInfo $info)
    {
        $attribute = $this->getAttribute();

        Service::setValue($object, $attribute, function($container, $setter) use ($newValue) {
            return $container->$setter($newValue);

        });
    }


    /**
     * @param $nodeDef
     * @param ClassDefinition $class
     * @return |null
     */
    public function getParentProcessor($nodeDef, ClassDefinition $class) {
        $nodeDefAttributes = $nodeDef["attributes"];
        $children = $nodeDefAttributes['childs'];
        if (!$children) {
            return null;
        }

        $firstChild = $children[0];
        $firstChildAttributes = $firstChild["attributes"];
        $service = $this->getGraphQlService();

        $factories = $service->getDataObjectMutationTypeGeneratorFactories();

        if ($firstChild["isOperator"]) {
            //  we only support the simple case with one child
            $operatorClass = $firstChildAttributes["class"];
            $typeName = strtolower($operatorClass);
            $mutationConfigGenerator = $factories->get('typegenerator_mutationoperator_' . $typeName);
            $config = $mutationConfigGenerator->getGraphQlMutationOperatorConfig($firstChild, $class);
        } else {
            $typeName = $firstChildAttributes["dataType"];
            $mutationConfigGenerator = $factories->get('typegenerator_dataobjectmutationdatatype_' . $typeName);
            $config = $mutationConfigGenerator->getGraphQlMutationFieldConfig($firstChild, $class);

        }


        $result = $config["processor"];
        return $result;
    }
}

