<?php

namespace WeSoonNet\LaravelPlus\Services\Com;

class TreeService
{

    /**
     * 生成递归树
     *
     * @param  array   $elements     二维数组
     * @param  string  $parentId     树根ID
     * @param  string  $idField      ID字段名
     * @param  string  $textField    TEXT字段名
     * @param  array   $otherFields  需要输出的其他字段 * ['child'] 为懒加载配置项
     * @param  string  $parent_id    父ID字段名
     * @param  array   $otherProps
     * @param  string  $idFieldName
     * @param  string  $textFieldName
     * @param  string  $childFieldName
     *
     * @param  string  $leafField
     *
     * @return array
     */
    public static function build(array $elements, $parentId = '0', $idField = 'id', $textField = 'text', array $otherFields = [], string $parent_id = 'parent_id', array $otherProps = [], string $idFieldName = 'id', string $textFieldName = 'text', string $childFieldName = 'children', string $leafField = 'isLeaf')
    {
        $parentId = (string) $parentId;
        $root     = null;
        $branch   = [];
        $fields   = array_merge($otherFields, [$idField, $textField, $idFieldName, $textFieldName, $parent_id], array_keys($otherProps));

        foreach ($elements as $index => $element)
        {
            if (is_object($element))
            {
                $element = $element->toArray();
            }

            if (isset($element[$idField]))
            {
                $element[$idFieldName] = $element[$idField];
            }

            if (isset($element[$textField]))
            {
                $element[$textFieldName] = $element[$textField];
            }

            foreach ($otherProps as $key => $value)
            {
                $element[$key] = $value;
            }

            $element = array_intersect_key($element, array_flip($fields));

            if ($element[$parent_id] == $parentId)
            {
                unset($elements[$index]);

                if (count($elements))
                {
                    $children = TreeService::build($elements, $element[$idFieldName], $idField, $textField, $otherFields, $parent_id, $otherProps, $idFieldName, $textFieldName, $childFieldName);

                    if ($children)
                    {
                        $element[$childFieldName] = $children;
                    }
                    else
                    {
                        if (isset($element['child']) && $element['child'])
                        {
                            $element[$childFieldName] = [];
                        }
                        else
                        {
                            $element[$leafField] = true;
                        }
                    }
                }
                else
                {
                    if (!isset($element['child']))
                    {
                        $element[$leafField] = true;
                    }
                }

                $branch[] = $element;

            }
        }

        return $branch;
    }

    /**
     * 数组转树形
     *
     * @param       $original
     * @param       $keys
     * @param  int  $level
     *
     * @return array
     */
    public static function groupToTree($original, $keys, $level = 0)
    {
        $converted = [];
        $key       = $keys[$level];
        $isDeepest = count($keys) - 1 == $level;

        $level++;
        $filtered = [];

        foreach ($original as $subArray)
        {
            $thisLevel = $subArray[$key];

            if ($isDeepest)
            {
                $converted[$thisLevel]['id']         = $thisLevel;
                $converted[$thisLevel]['name']       = $thisLevel;
                $converted[$thisLevel]['children'][] = $subArray;
            }
            else
            {
                $converted[$thisLevel] = [];
            }

            $filtered[$thisLevel]['children'][] = $subArray;
        }

        if (!$isDeepest)
        {
            foreach (array_keys($converted) as $value)
            {
                $converted[$value] = self::groupToTree($filtered[$value], $keys, $level);
            }
        }

        return array_values($converted);
    }
}
