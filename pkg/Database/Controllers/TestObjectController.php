<?php

namespace Package\Database\Controllers;

use LDAP\Result;
use Package\CCMS\Controllers\BaseController;
use Package\CCMS\Extensions\Route;
use Package\CCMS\Extensions\RoutePrefix;
use Package\CCMS\Models\HTTP\Method;
use Package\CCMS\Models\HTTP\StatusCode;
use Package\CCMS\Models\JsonResponse;
use Package\CCMS\Models\Response;
use Package\Database\Models\TestObject;
use Package\Database\Services\DatabaseService;
use Package\Database\Views\SetupView;

#[RoutePrefix('api/database/testobjects')]
class TestObjectController extends BaseController
{
    // These endpoints should only be enabled when running in debug/test mode,
    //  but core doesn't have a method to detect for this yet.

    private function ServerInTestMode() : bool {
        return true;
    }

    #[Route(Method::GET, '~api/database/testobjects')]
    public function GetListOfObjects() : Response {
        if (!$this->ServerInTestMode()) {
            return new Response(
                status: StatusCode::NotFound
            );
        }

        // get array of all objects
        $objects = TestObject::GetAll();
        $serializedObjects = [];
        foreach ($objects as $object) {
            $serializedObjects[] = [
                'id' => $object->id,
                'insertedTime' => $object->insertedTime,
                'updatedTime' => $object->updatedTime,
                'name' => $object->name,
                'int' => $object->int,
                'float' => $object->float,
                'bool' => $object->bool
            ];
        }

        return new JsonResponse(
            data: [
                'testobjects' => $serializedObjects,
            ],
            status: StatusCode::OK,
        );
    }

    #[Route(Method::POST, 'create')]
    #[Route(Method::POST, 'update')]
    #[Route(Method::POST, 'update/{id}')]
    public function CreateOrUpdateObject() : Response {
        if (!$this->ServerInTestMode()) {
            return new Response(
                status: StatusCode::NotFound
            );
        }
        // validate parameters
        $name = $this->request->Args['name'];
        $float = $this->request->Args['name'];
        $int = $this->request->Args['name'];
        $bool = $this->request->Args['name'];

        // first, search for existing object with id (if provided)
        $object = TestObject::LoadFromId($this->request->Args['id']);
        if ($object === null) {
            // if none found:
            $object = new TestObject($name, $int, $float, $bool);
        }
        $object->name = $name;
        $object->int = $int;
        $object->float = $float;
        $object->bool = $bool;
        
        $object->Save();
    }

    #[Route(Method::POST, 'remove')]
    #[Route(Method::POST, 'remove/{id}')]
    public function RemoveObject() : Response {
        if (!$this->ServerInTestMode()) {
            return new Response(
                status: StatusCode::NotFound
            );
        }

        // find existing object with id (if provided)
        
        $object = new TestObject('a',1,1.2,false);
        $object->Delete();
    }
}