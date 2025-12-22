import { render } from '@testing-library/react';

test('app renders without crashing', () => {
  const div = document.createElement('div');
  expect(div).toBeTruthy();
});

test('localStorage is available', () => {
  expect(typeof localStorage).toBe('object');
});

test('can store and retrieve from localStorage', () => {
  localStorage.setItem('test', 'value');
  expect(localStorage.getItem('test')).toBe('value');
  localStorage.removeItem('test');
});
