<?php

namespace App\Controllers;

use App\Libraries\AuthContext;
use App\Models\CustomerModel;
use App\Models\NoteModel;
use CodeIgniter\RESTful\ResourceController;

class NoteController extends ResourceController
{
    protected $modelName = NoteModel::class;
    protected $format    = 'json';

    public function index($customerId = null)
    {
        $customer = (new CustomerModel())->find($customerId);
        if (!$this->canAccess($customer)) {
            return $this->failNotFound('Customer not found');
        }

        return $this->respond(
            $this->model->where('customer_id', $customerId)->orderBy('created_at', 'DESC')->findAll()
        );
    }

    public function create($customerId = null)
    {
        $customer = (new CustomerModel())->find($customerId);
        if (!$this->canAccess($customer)) {
            return $this->failNotFound('Customer not found');
        }

        $data = $this->request->getJSON(true) ?? [];
        $data['customer_id'] = $customerId;
        $data['user_id']     = AuthContext::id();

        $id = $this->model->insert($data);
        if (!$id) {
            return $this->failValidationErrors($this->model->errors());
        }

        return $this->respondCreated($this->model->find($id));
    }

    private function canAccess(?array $customer): bool
    {
        if (!$customer) {
            return false;
        }
        return AuthContext::isAdmin() || (int) $customer['owner_id'] === AuthContext::id();
    }
}
