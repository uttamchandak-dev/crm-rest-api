<?php

namespace App\Controllers;

use App\Libraries\AuthContext;
use App\Models\CustomerModel;
use CodeIgniter\RESTful\ResourceController;

class CustomerController extends ResourceController
{
    protected $modelName = CustomerModel::class;
    protected $format    = 'json';

    public function index()
    {
        $builder = $this->model;

        if (!AuthContext::isAdmin()) {
            $builder = $builder->where('owner_id', AuthContext::id());
        }

        return $this->respond($builder->orderBy('created_at', 'DESC')->findAll());
    }

    public function show($id = null)
    {
        $customer = $this->model->find($id);
        if (!$this->canAccess($customer)) {
            return $this->failNotFound('Customer not found');
        }
        return $this->respond($customer);
    }

    public function create()
    {
        $data = $this->request->getJSON(true) ?? [];
        $data['owner_id'] = AuthContext::id();

        $id = $this->model->insert($data);
        if (!$id) {
            return $this->failValidationErrors($this->model->errors());
        }

        return $this->respondCreated($this->model->find($id));
    }

    public function update($id = null)
    {
        $customer = $this->model->find($id);
        if (!$this->canAccess($customer)) {
            return $this->failNotFound('Customer not found');
        }

        $data = $this->request->getJSON(true) ?? [];
        unset($data['owner_id']); // ownership can't be reassigned via this endpoint

        if (!$this->model->update($id, $data)) {
            return $this->failValidationErrors($this->model->errors());
        }

        return $this->respond($this->model->find($id));
    }

    public function delete($id = null)
    {
        if (!AuthContext::isAdmin()) {
            return $this->failForbidden('Only admins can delete customers');
        }

        $customer = $this->model->find($id);
        if (!$customer) {
            return $this->failNotFound('Customer not found');
        }

        $this->model->delete($id);
        return $this->respondDeleted(['id' => $id]);
    }

    private function canAccess(?array $customer): bool
    {
        if (!$customer) {
            return false;
        }
        return AuthContext::isAdmin() || (int) $customer['owner_id'] === AuthContext::id();
    }
}
