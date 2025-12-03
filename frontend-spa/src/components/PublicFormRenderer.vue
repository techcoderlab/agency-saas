<script setup>
import { computed } from 'vue'
import { Listbox, ListboxButton, ListboxOptions, ListboxOption } from '@headlessui/vue'

const props = defineProps({
  schema: { type: Array, default: () => [] },
  modelValue: { type: Object, default: () => ({}) },
  errors: { type: Object, default: () => ({}) },
})

const emit = defineEmits(['update:modelValue'])

const valueFor = (field) =>
  computed(() => props.modelValue[field.name] ?? (field.type === 'checkbox-group' || field.multiple ? [] : ''))

const updateField = (name, value) => {
  emit('update:modelValue', { ...props.modelValue, [name]: value })
}

const handleCheckboxGroup = (field, optionValue, checked) => {
  const current = Array.isArray(props.modelValue[field.name]) ? [...props.modelValue[field.name]] : []
  if (checked) {
    if (!current.includes(optionValue)) current.push(optionValue)
  } else {
    const idx = current.indexOf(optionValue)
    if (idx !== -1) current.splice(idx, 1)
  }
  updateField(field.name, current)
}
</script>

<template>
  <form class="space-y-6" @submit.prevent>
    <div v-for="(field, index) in schema" :key="field.name || index" class="space-y-1.5">
      
      <div class="flex items-center justify-between">
        <label class="block text-sm font-semibold text-slate-700">
          {{ field.label || field.name }}
          <span v-if="field.required" class="text-red-500">*</span>
        </label>
        <span v-if="field.hint" class="text-xs text-slate-400 italic">{{ field.hint }}</span>
      </div>

      <div class="relative">
        
        <input
          v-if="['text', 'email', 'number', 'tel', 'url'].includes(field.type)"
          :type="field.type"
          class="block w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 shadow-sm focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none transition-all"
          :placeholder="field.placeholder"
          :value="valueFor(field).value"
          @input="updateField(field.name, $event.target.value)"
        />

        <textarea
          v-else-if="field.type === 'textarea'"
          class="block w-full rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 shadow-sm focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none transition-all"
          rows="4"
          :placeholder="field.placeholder"
          :value="valueFor(field).value"
          @input="updateField(field.name, $event.target.value)"
        ></textarea>

        <Listbox
          v-else-if="field.type === 'select' && !field.multiple"
          :model-value="valueFor(field).value || ''"
          @update:model-value="(val) => updateField(field.name, val)"
        >
          <div class="relative">
            <ListboxButton class="relative w-full cursor-default rounded-lg border border-slate-300 bg-white py-2.5 pl-4 pr-10 text-left shadow-sm focus:border-primary focus:ring-2 focus:ring-primary/20 focus:outline-none">
              <span class="block truncate text-sm" :class="!valueFor(field).value ? 'text-slate-400' : 'text-slate-900'">
                {{ (field.options || []).find((o) => o.value === valueFor(field).value)?.label || field.placeholder || 'Select an option' }}
              </span>
              <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
                <svg class="h-5 w-5 text-slate-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M10 3a1 1 0 01.707.293l3 3a1 1 0 01-1.414 1.414L10 5.414 7.707 7.707a1 1 0 01-1.414-1.414l3-3A1 1 0 0110 3zm-3.707 9.293a1 1 0 011.414 0L10 14.586l2.293-2.293a1 1 0 011.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
              </span>
            </ListboxButton>
            <ListboxOptions class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black/5 focus:outline-none sm:text-sm">
              <ListboxOption v-for="option in field.options || []" :key="option.value" :value="option.value" v-slot="{ active, selected }">
                <li :class="[active ? 'bg-primary/10 text-primary' : 'text-slate-900', 'relative cursor-default select-none py-2 pl-3 pr-9']">
                  <span :class="[selected ? 'font-semibold' : 'font-normal', 'block truncate']">{{ option.label }}</span>
                  <span v-if="selected" :class="[active ? 'text-primary' : 'text-primary', 'absolute inset-y-0 right-0 flex items-center pr-4']">
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                  </span>
                </li>
              </ListboxOption>
            </ListboxOptions>
          </div>
        </Listbox>

        <div v-else-if="field.type === 'checkbox-group'" class="space-y-2 pt-1">
          <label v-for="option in field.options || []" :key="option.value" class="flex items-center gap-3 p-2 rounded-lg border border-slate-100 bg-slate-50 hover:bg-slate-100 hover:border-slate-200 cursor-pointer transition-colors">
            <input type="checkbox" class="h-4 w-4 rounded border-slate-300 text-primary focus:ring-primary" :value="option.value" :checked="(valueFor(field).value || []).includes(option.value)" @change="handleCheckboxGroup(field, option.value, $event.target.checked)" />
            <span class="text-sm text-slate-700 font-medium">{{ option.label }}</span>
          </label>
        </div>

        <div v-else-if="field.type === 'radio-group'" class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
          <label v-for="option in field.options || []" :key="option.value" class="flex items-center gap-3 p-3 rounded-lg border cursor-pointer transition-all"
            :class="valueFor(field).value === option.value ? 'border-primary bg-primary/5 ring-1 ring-primary' : 'border-slate-200 bg-white hover:bg-slate-50'">
            <input type="radio" :name="field.name" class="h-4 w-4 border-slate-300 text-primary focus:ring-primary" :value="option.value" :checked="valueFor(field).value === option.value" @change="updateField(field.name, option.value)" />
            <span class="text-sm font-medium" :class="valueFor(field).value === option.value ? 'text-primary' : 'text-slate-700'">{{ option.label }}</span>
          </label>
        </div>

        <div v-else-if="field.type === 'range'" class="pt-4 px-1">
           <div class="relative flex items-center">
              <input type="range" class="w-full h-2 bg-slate-200 rounded-lg appearance-none cursor-pointer accent-primary" 
                 :min="field.min || 0" :max="field.max || 100" 
                 :value="valueFor(field).value || field.min || 0" 
                 @input="updateField(field.name, Number($event.target.value))" 
              />
           </div>
           <div class="mt-2 flex justify-between text-xs text-slate-500 font-medium">
              <span>{{ field.min || 0 }}</span>
              <span class="text-primary font-bold text-sm">{{ valueFor(field).value || field.min || 0 }}</span>
              <span>{{ field.max || 100 }}</span>
           </div>
        </div>

      </div>

      <p v-if="errors[field.name]" class="text-xs text-red-500 font-medium flex items-center gap-1 mt-1">
        <svg class="w-3 h-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
        {{ errors[field.name] }}
      </p>

    </div>
  </form>
</template>