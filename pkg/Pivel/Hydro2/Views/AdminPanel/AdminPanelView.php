<?php

namespace Package\Pivel\Hydro2\Views\AdminPanel;

use Package\Pivel\Hydro2\Views\BaseWebView;
use Package\Pivel\Hydro2\Views\Components\MasterDetail\MasterDetailViewPage;

class AdminPanelView extends BaseWebView
{
    protected array $NavTree = [];
    /** @var MasterDetailViewPage[] */
    protected array $ContentPages = [];

    public function __construct(
        protected array $Nodes,
        protected string $DefaultNode = '',
    ) {
        $this->NavTree = $this->GenerateNavTree($this->Nodes);

        // for each node:
        // initialize a MasterDetailViewPage and add to ContentPages array
        foreach($this->Nodes as $key=>$node) {
            if (!isset($node['view'])) {
                continue;
            }

            $this->ContentPages[] = new MasterDetailViewPage($key, $node['view']);
        }
    }

    private function GenerateNavTree($nodes) : array {
        $tree = [];
        foreach ($nodes as $key=>$node) {
            // check if node has a parent that exists in $Node
            $parentKey = implode('/', explode('/', $key, -1));
            if (!empty($parentKey) && in_array($parentKey, array_keys($nodes))) {
                // it has a parent, so it wil be added during the recursive stage.
                continue;
            }

            $childNodes = array_filter($nodes, function($nodeKey) use ($key){
                return str_starts_with($nodeKey, $key.'/');
            }, ARRAY_FILTER_USE_KEY);

            $children = $this->GenerateNavTree($childNodes);

            $tree[] = [
                'key' => $key,
                'name' => $node['name'],
                'children' => $children,
            ];
        }

        return $tree;
    }
}