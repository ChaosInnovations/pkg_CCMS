<?php

namespace Package\Pivel\Hydro2\Core\Views;

use Exception;
use ReflectionClass;
use ReflectionException;

class BaseView
{
    protected ReflectionClass $rc;
    protected array $properties;

    public function Render() : string {
        // load template from View attribute? or just look for template with matching class name?
        $this->rc = new ReflectionClass($this);        
        $this->properties = array_map(fn($a) => $a->name,$this->rc->getProperties());
        $templatePath = str_replace('.php','.template.html', $this->rc->getFileName());
        $template = file_get_contents($templatePath);
        $rendered = $this->ResolveTemplate($template);
        return $rendered;
    }

    protected function ResolveTemplate(string $template) : string {
        // template placeholder format
        // {{name:space:class|arg1,"arg2",$vararg3}}
        // {{arg1}}
        
        // find all template placeholders. If there are none, return the original template string as the result.
        if (!preg_match_all("/(?<={{)[^{}]+(?=}})/",$template,$placeholders,PREG_PATTERN_ORDER)) {
            // no placeholders.
            return $template;
        }
        // filter duplicates. Only need to evaluate each placeholder once.
        $placeholders = array_values(array_unique($placeholders[0]));
        // evaluate each placeholder.
        // replace template placeholders with matching results
        foreach ($placeholders as $placeholder) {
            $result = $this->EvaluatePlaceholder($placeholder);
            $template = str_replace('{{' . $placeholder . '}}', $result, $template);
        }
        // return results
        return $template;
    }

    protected function EvaluatePlaceholder(string $placeholder) : string {
        // {{name\space\class|arg1,"arg2",$vararg3}}
        // {{arg1}}
        
        // split by '|' max once. if 2 results, classstr is [0] and argsstr is [1] else argsstr is [0]
        $parts = explode('|', $placeholder, 2);
        $classstr = null;
        if (count($parts) > 1) {
            $classstr = $parts[0];
        }
        // split argsstr by ',' that are not inside a pair of '' or "" ==> args[]
        $args = preg_split("/(^[\"'])|([\"']?(?<!\\\\),[\"']?)|([\"']$)/",$parts[count($parts)-1],-1,PREG_SPLIT_NO_EMPTY);
        // for each arg, evaluate to int, float, bool, string, or variable content
        foreach ($args as $key => $arg) {
            if (str_starts_with($arg, '$')) {
                $arg = substr($arg, 1);
                // this is a variable
                if (!in_array($arg, $this->properties)) {
                    // Variable doesn't exist. should we prevent this from being displayed?
                    $args[$key] = '';
                    continue;
                }
                $propertyValue = $this->$arg;
                // if property type extends BaseView, render first then replace
                // TODO: need to prevent circular inclusions
                if ($propertyValue instanceof BaseView) {
                    $args[$key] = $propertyValue->Render();
                    continue;
                }
                $args[$key] = $propertyValue;
            }

            // don't need to do anything with string because it is already a string
            // don't need to do anything with int/float/bool because of implicit type conversion
            // -> may revisit this since some view constructors might have typed args other than string
        }
        
        // if no classstr, return arg[0]
        if ($classstr === null) {
            return $args[0];
        }
        
        // if there is a classstr which refers to a valid class that extends BaseView
        // -> instantiate the View object, render, and return result.
        try {
            $class = new ReflectionClass($classstr);
            $instance = $class->newInstance(...$args);
        } catch (ReflectionException) {
            // class doesn't exist, or if there are more that 0 args and the class does not have a public constructor
            return '';
        }

        if (!($instance instanceof BaseView)) {
            // not an instance of BaseView
            return '';
        }

        //return 'instance of ' . $classstr . ' with args ' . implode(', ', $args);
        return $instance->Render();
    }
}