<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreContactAttributeRequest;
use App\Http\Resources\ContactAttributeResource;
use App\Models\Contact;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpFoundation\Response;

class ContactAttributeController extends Controller
{

    public function store(StoreContactAttributeRequest $request, Contact $contact)
    {
        $this->authorize('update', $contact);

        try {
            $attribute = $contact->attributes()->create($request->validated());

            return response()->json([
                'status'  => 'success',
                'message' => 'Attribute added successfully',
                'data'    => new ContactAttributeResource($attribute),
            ], Response::HTTP_CREATED);
        } catch (QueryException $e) {
            if ($e->errorInfo[1] == 1062) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'This attribute key already exists for this contact.',
                ], Response::HTTP_CONFLICT);
            }
            throw $e;
        }
    }

}
