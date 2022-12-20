<?php

namespace Package\Pivel\Hydro2\Database\Controllers;

use Package\Pivel\Hydro2\Core\Controllers\BaseController;
use Package\Pivel\Hydro2\Core\Extensions\Route;
use Package\Pivel\Hydro2\Core\Extensions\RoutePrefix;
use Package\Pivel\Hydro2\Core\Models\HTTP\Method;
use Package\Pivel\Hydro2\Core\Models\HTTP\StatusCode;
use Package\Pivel\Hydro2\Core\Models\JsonResponse;
use Package\Pivel\Hydro2\Core\Models\Response;
use Package\Pivel\Hydro2\Database\Models\TestObject;

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

    #[Route(Method::GET, '{id}')]
    public function GetObject() : Response {
        if (!$this->ServerInTestMode()) {
            return new Response(
                status: StatusCode::NotFound
            );
        }

        if (!isset($this->request->Args['id'])) {
            return new JsonResponse(
                data: [
                    'validation_errors' => [
                        [
                            'name' => 'id',
                            'description' => 'TestObject ID',
                            'message' => 'Argument not provided.',
                        ],
                    ],
                ],
                status: StatusCode::BadRequest,
                error_message: 'One or more arguments are mising.'
            );
        }

        // first, search for existing object with id (if provided)
        $object = TestObject::LoadFromId($this->request->Args['id']);

        // get array of all objects
        $serializedObjects = [];
        if ($object !== null) {
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
    #[Route(Method::POST, '{id}/update')]
    public function CreateOrUpdateObject() : Response {
        if (!$this->ServerInTestMode()) {
            return new Response(
                status: StatusCode::NotFound
            );
        }
        // validate parameters
        $name = $this->request->Args['name'];
        $float = $this->request->Args['float'];
        $int = $this->request->Args['int'];
        $bool = $this->request->Args['bool'];

        // first, search for existing object with id (if provided)
        $object = null;
        if (isset($this->request->Args['id'])) {
            $object = TestObject::LoadFromId($this->request->Args['id']);
        }
        if ($object == null) {
            // if none found:
            $object = new TestObject($name, $int, $float, $bool);
        }
        $object->name = $name;
        $object->int = $int;
        $object->float = $float;
        $object->bool = $bool;
        
        if (!$object->Save()) {
            return new JsonResponse(
                null,
                StatusCode::InternalServerError,
                "There was a problem with the database."
            );
        }

        return new JsonResponse(
            status: StatusCode::OK,
        );
    }

    #[Route(Method::POST, 'remove')]
    #[Route(Method::POST, '{id}/remove')]
    public function RemoveObject() : Response {
        if (!$this->ServerInTestMode()) {
            return new Response(
                status: StatusCode::NotFound
            );
        }

        // find existing object with id (if provided)
        if (!isset($this->request->Args['id'])) {
            return new JsonResponse(
                data: [
                    'validation_errors' => [
                        [
                            'name' => 'id',
                            'description' => 'TestObject ID',
                            'message' => 'Argument not provided.',
                        ],
                    ],
                ],
                status: StatusCode::BadRequest,
                error_message: 'One or more arguments are mising.'
            );
        }

        // first, search for existing object with id (if provided)
        $object = TestObject::LoadFromId($this->request->Args['id']);

        if ($object != null && !$object->Delete()) {
            return new JsonResponse(
                null,
                StatusCode::InternalServerError,
                "There was a problem with the database."
            );
        }

        return new JsonResponse(
            status: StatusCode::OK,
        );
    }
}