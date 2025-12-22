describe('Booking Service', () => {
  beforeEach(() => {
    localStorage.clear();
  });

  test('localStorage can store tokens', () => {
    const testToken = 'test-jwt-token';
    localStorage.setItem('token', testToken);
    expect(localStorage.getItem('token')).toBe(testToken);
  });

  test('localStorage can store user email', () => {
    const testEmail = 'test@example.com';
    localStorage.setItem('userEmail', testEmail);
    expect(localStorage.getItem('userEmail')).toBe(testEmail);
  });

  test('localStorage can clear all data', () => {
    localStorage.setItem('token', 'test-token');
    localStorage.setItem('userEmail', 'test@example.com');
    localStorage.clear();
    expect(localStorage.getItem('token')).toBeNull();
    expect(localStorage.getItem('userEmail')).toBeNull();
  });
});
