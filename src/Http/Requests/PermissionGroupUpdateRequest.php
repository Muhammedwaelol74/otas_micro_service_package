<?php

namespace Solutionplus\MicroService\Http\Requests;

use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Http\FormRequest;
use Solutionplus\MicroService\Models\Permission;

class PermissionGroupUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => [
                'sometimes',
                'string',
                'min:2',
                'max:191',
                Rule::unique('permission_group_translations', 'name')->where(function ($query) {
                    return $query->where('permission_group_translations.permission_group_id', '!=', request()->permission_group->id);
                }),
            ],
            'permissions' => 'sometimes|array',
            'permissions.*' => 'required|exists:permissions,id',
        ];
    }

    public function getValidatorInstance()
    {
        request()->locale = request()->input('locale');
        return parent::getValidatorInstance();
    }

    public function updatePermissionGroup()
    {
        $currentTranslationNamespace = config('translatable.translation_models_path');
        config(['translatable.translation_models_path' => 'Solutionplus\MicroService\Models']);
        DB::transaction(function () {
            if ($this->exists('name')) {
                $this->permission_group->update([]);
            }
            $this->updateGroupPermissions();
        });
        config(['translatable.translation_models_path' => $currentTranslationNamespace]);
        return $this->permission_group->refresh();
    }

    private function updateGroupPermissions()
    {
        if ($this->exists('permissions')) {
            Permission::whereIn('id', $this->permissions)
                ->where('permission_group_id', '!=', $this->permission_group->id)
                ->update([
                    'permission_group_id' => $this->permission_group->id,
                ]);
        }
        return $this;
    }
}
