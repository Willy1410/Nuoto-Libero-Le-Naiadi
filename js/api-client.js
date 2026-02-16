// API Client per connessione al backend
const API_BASE_URL = 'http://localhost:3000/api';

class ApiClient {
  constructor() {
    this.token = localStorage.getItem('authToken');
  }

  // Headers con autenticazione
  getHeaders() {
    const headers = {
      'Content-Type': 'application/json'
    };

    if (this.token) {
      headers['Authorization'] = `Bearer ${this.token}`;
    }

    return headers;
  }

  // Gestione risposta
  async handleResponse(response) {
    const data = await response.json();

    if (!response.ok) {
      throw new Error(data.message || 'Errore nella richiesta');
    }

    return data;
  }

  // Gestione errore
  handleError(error) {
    console.error('API Error:', error);
    
    if (error.message.includes('Token') || error.message.includes('401')) {
      // Token scaduto/invalido -> logout
      this.logout();
      window.location.href = 'login.html';
    }

    throw error;
  }

  // === AUTH ===

  async login(username, password) {
    try {
      const response = await fetch(`${API_BASE_URL}/auth/login`, {
        method: 'POST',
        headers: this.getHeaders(),
        body: JSON.stringify({ username, password })
      });

      const data = await this.handleResponse(response);
      
      if (data.success) {
        this.token = data.data.token;
        localStorage.setItem('authToken', this.token);
        localStorage.setItem('user', JSON.stringify(data.data.user));
      }

      return data;
    } catch (error) {
      return this.handleError(error);
    }
  }

  async register(userData) {
    try {
      const response = await fetch(`${API_BASE_URL}/auth/register`, {
        method: 'POST',
        headers: this.getHeaders(),
        body: JSON.stringify(userData)
      });

      const data = await this.handleResponse(response);
      
      if (data.success) {
        this.token = data.data.token;
        localStorage.setItem('authToken', this.token);
        localStorage.setItem('user', JSON.stringify(data.data.user));
      }

      return data;
    } catch (error) {
      return this.handleError(error);
    }
  }

  async logout() {
    try {
      await fetch(`${API_BASE_URL}/auth/logout`, {
        method: 'POST',
        headers: this.getHeaders()
      });
    } catch (error) {
      console.error('Logout error:', error);
    } finally {
      this.token = null;
      localStorage.removeItem('authToken');
      localStorage.removeItem('user');
      localStorage.removeItem('session');
    }
  }

  async getMe() {
    try {
      const response = await fetch(`${API_BASE_URL}/auth/me`, {
        method: 'GET',
        headers: this.getHeaders()
      });

      return await this.handleResponse(response);
    } catch (error) {
      return this.handleError(error);
    }
  }

  async changePassword(oldPassword, newPassword) {
    try {
      const response = await fetch(`${API_BASE_URL}/auth/change-password`, {
        method: 'POST',
        headers: this.getHeaders(),
        body: JSON.stringify({ oldPassword, newPassword })
      });

      return await this.handleResponse(response);
    } catch (error) {
      return this.handleError(error);
    }
  }

  // === USERS ===

  async getAllUsers(params = {}) {
    try {
      const queryString = new URLSearchParams(params).toString();
      const response = await fetch(`${API_BASE_URL}/users?${queryString}`, {
        method: 'GET',
        headers: this.getHeaders()
      });

      return await this.handleResponse(response);
    } catch (error) {
      return this.handleError(error);
    }
  }

  async getUserById(userId) {
    try {
      const response = await fetch(`${API_BASE_URL}/users/${userId}`, {
        method: 'GET',
        headers: this.getHeaders()
      });

      return await this.handleResponse(response);
    } catch (error) {
      return this.handleError(error);
    }
  }

  async getUserByQR(qrCode) {
    try {
      const response = await fetch(`${API_BASE_URL}/users/qr/${qrCode}`, {
        method: 'GET',
        headers: this.getHeaders()
      });

      return await this.handleResponse(response);
    } catch (error) {
      return this.handleError(error);
    }
  }

  async updateUser(userId, userData) {
    try {
      const response = await fetch(`${API_BASE_URL}/users/${userId}`, {
        method: 'PUT',
        headers: this.getHeaders(),
        body: JSON.stringify(userData)
      });

      return await this.handleResponse(response);
    } catch (error) {
      return this.handleError(error);
    }
  }

  async deleteUser(userId) {
    try {
      const response = await fetch(`${API_BASE_URL}/users/${userId}`, {
        method: 'DELETE',
        headers: this.getHeaders()
      });

      return await this.handleResponse(response);
    } catch (error) {
      return this.handleError(error);
    }
  }

  async getStats() {
    try {
      const response = await fetch(`${API_BASE_URL}/users/stats`, {
        method: 'GET',
        headers: this.getHeaders()
      });

      return await this.handleResponse(response);
    } catch (error) {
      return this.handleError(error);
    }
  }

  // === ENTRIES & PACKAGES ===

  async registerEntry(userId) {
    try {
      const response = await fetch(`${API_BASE_URL}/entries/register`, {
        method: 'POST',
        headers: this.getHeaders(),
        body: JSON.stringify({ userId })
      });

      return await this.handleResponse(response);
    } catch (error) {
      return this.handleError(error);
    }
  }

  async purchasePackage(userId, packageType, paymentMethod, amount) {
    try {
      const response = await fetch(`${API_BASE_URL}/entries/purchase`, {
        method: 'POST',
        headers: this.getHeaders(),
        body: JSON.stringify({ userId, packageType, paymentMethod, amount })
      });

      return await this.handleResponse(response);
    } catch (error) {
      return this.handleError(error);
    }
  }

  async getDailyReport(date = null) {
    try {
      const queryString = date ? `?date=${date}` : '';
      const response = await fetch(`${API_BASE_URL}/entries/report/daily${queryString}`, {
        method: 'GET',
        headers: this.getHeaders()
      });

      return await this.handleResponse(response);
    } catch (error) {
      return this.handleError(error);
    }
  }

  // === HELPERS ===

  isAuthenticated() {
    return !!this.token && !!localStorage.getItem('user');
  }

  getUser() {
    const userStr = localStorage.getItem('user');
    return userStr ? JSON.parse(userStr) : null;
  }

  getUserRole() {
    const user = this.getUser();
    return user ? user.role : null;
  }
}

// Export singleton
window.API = new ApiClient();
