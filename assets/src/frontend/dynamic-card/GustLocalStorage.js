class GustLocalStorage {
	static key = '__gust_medias';

	static getItems() {
		const localStorageData = localStorage.getItem(GustLocalStorage.key);
		return localStorageData ? JSON.parse(localStorageData) : {};
	}

	static getItem(key) {
		const data = GustLocalStorage.getItems();
		return data[key] ?? null;
	}

	static setItems(newData) {
		const data = GustLocalStorage.getItems();
		Object.entries(newData).forEach(([key, value]) => {
			data[key] = value;
		})
		localStorage.setItem(GustLocalStorage.key, JSON.stringify(data));
	}

	static setItem(key, value) {
		const data = GustLocalStorage.getItems();
		data[key] = value;
		localStorage.setItem(GustLocalStorage.key, JSON.stringify(data));
	}

	static removeItem(key) {
		const data = GustLocalStorage.getItems();
		if (data[key]) {
			delete data[key];
			localStorage.setItem(GustLocalStorage.key, JSON.stringify(data));
		}
	}

	static appendMedia(id) {
		const ids = GustLocalStorage.getItem('media_ids');
		ids.push(id);
		GustLocalStorage.setItem('media_ids', ids);
	}
}

export default GustLocalStorage;
