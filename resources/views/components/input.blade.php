@props([
    'name' => '',
    'label' => '',
    'type' => 'text',
    'value' => '',
    'required' => false,
    'placeholder' => '',
    'help' => '',
    'step' => '',
    'min' => '',
    'max' => '',
    'disabled' => false,
    'readonly' => false,
    'class' => '',
    'id' => '',
    'options' => [],
    'multiple' => false,
    'rows' => 3,
])

@php
    $fieldId = $id ?? $name;
    $isSelect = in_array($type, ['select', 'select2']);
    $isTextarea = $type === 'textarea';
    $isCheckbox = $type === 'checkbox';
    
    $inputClasses = $isSelect ? 'form-select' : 'form-control';
    if ($isCheckbox) {
        $inputClasses = 'form-check-input';
    }
    if ($class) {
        $inputClasses .= ' ' . $class;
    }
    if ($errors->has($name)) {
        $inputClasses .= ' is-invalid';
    }
    
    $wrapperClasses = 'mb-3';
    if ($isCheckbox) {
        $wrapperClasses = 'mb-3 form-check';
    }
@endphp

<div class="{{ $wrapperClasses }}">
    @if($label && !$isCheckbox)
        <label for="{{ $fieldId }}" class="form-label">
            {{ $label }}
            @if($required)
                <span class="text-danger">*</span>
            @endif
        </label>
    @endif
    
    @if($isSelect)
        <select 
            name="{{ $name }}" 
            id="{{ $fieldId }}" 
            class="{{ $inputClasses }}"
            @if($required) required @endif
            @if($disabled) disabled @endif
            @if($multiple) multiple @endif
            {{ $attributes->merge([]) }}
        >
            @if($placeholder && !$multiple)
                <option value="">{{ $placeholder }}</option>
            @endif
            @foreach($options as $optionValue => $optionLabel)
                <option value="{{ $optionValue }}" @if($value == $optionValue) selected @endif>
                    {{ $optionLabel }}
                </option>
            @endforeach
        </select>
        
    @elseif($isTextarea)
        <textarea 
            name="{{ $name }}" 
            id="{{ $fieldId }}" 
            class="{{ $inputClasses }}"
            rows="{{ $rows }}"
            placeholder="{{ $placeholder }}"
            @if($required) required @endif
            @if($disabled) disabled @endif
            @if($readonly) readonly @endif
            {{ $attributes->merge([]) }}
        >{{ old($name, $value) }}</textarea>
        
    @elseif($isCheckbox)
        <input 
            type="checkbox"
            name="{{ $name }}"
            id="{{ $fieldId }}"
            class="{{ $inputClasses }}"
            value="1"
            @if(old($name, $value)) checked @endif
            @if($required) required @endif
            @if($disabled) disabled @endif
            @if($readonly) readonly @endif
            {{ $attributes->merge([]) }}
        >
        @if($label)
            <label class="form-check-label" for="{{ $fieldId }}">
                {{ $label }}
                @if($required)
                    <span class="text-danger">*</span>
                @endif
            </label>
        @endif
        
    @else
        <input 
            type="{{ $type }}"
            name="{{ $name }}"
            id="{{ $fieldId }}"
            class="{{ $inputClasses }}"
            placeholder="{{ $placeholder }}"
            value="{{ old($name, $value) }}"
            @if($required) required @endif
            @if($disabled) disabled @endif
            @if($readonly) readonly @endif
            @if($step) step="{{ $step }}" @endif
            @if($min) min="{{ $min }}" @endif
            @if($max) max="{{ $max }}" @endif
            {{ $attributes->merge([]) }}
        >
    @endif
    
    @if($help)
        <div class="form-text text-muted small">{{ $help }}</div>
    @endif
    
    @error($name)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>